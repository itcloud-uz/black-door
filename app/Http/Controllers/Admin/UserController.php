<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Obj;
use App\Models\ObjectManager;
use App\Models\ObjectManagerHistory;
use App\Enums\UserRole;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // Get active objects
        $objects = Obj::where('is_active', true)->get();
        return view('admin.users.create', compact('objects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'pin_code' => 'nullable|required_if:role,financier|string|size:4|regex:/^\d{4}$/',
            'object_id' => 'nullable|required_if:role,manager|exists:objects,id',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ];

        if ($request->role === 'financier' && $request->filled('pin_code')) {
            $userData['pin_code'] = Hash::make($request->pin_code);
        }

        DB::transaction(function () use ($userData, $request) {
            $user = User::create($userData);
            AuditLogger::log('create_user', $user, null, $user->toArray());

            // If manager, assign to object
            if ($request->role === 'manager' && $request->filled('object_id')) {
                $objectId = (int)$request->object_id;

                // Enforce 1:1 manager-object constraint:
                // Check if this object already has a manager assigned
                $oldManagerAssignment = ObjectManager::where('object_id', $objectId)->first();
                if ($oldManagerAssignment) {
                    // Update history for old manager
                    ObjectManagerHistory::where('object_id', $objectId)
                        ->whereNull('unassigned_at')
                        ->update([
                            'unassigned_at' => now(),
                            'reason' => 'Yangi menejer tayinlandi.'
                        ]);
                    $oldManagerAssignment->delete();
                }

                // Create new manager assignment
                ObjectManager::create([
                    'object_id' => $objectId,
                    'user_id' => $user->id,
                    'assigned_at' => now(),
                ]);

                ObjectManagerHistory::create([
                    'object_id' => $objectId,
                    'user_id' => $user->id,
                    'assigned_at' => now(),
                    'reason' => 'Menejer tayinlandi.'
                ]);
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'Foydalanuvchi muvaffaqiyatli yaratildi.');
    }

    public function toggleActive(User $user)
    {
        $oldValues = $user->toArray();
        $user->is_active = ! $user->is_active;
        $user->save();

        AuditLogger::log('toggle_user_status', $user, $oldValues, $user->toArray());

        $statusStr = $user->is_active ? 'faollashtirildi' : 'faolsizlantirildi';
        return redirect()->route('admin.users.index')->with('success', "Foydalanuvchi muvaffaqiyatli {$statusStr}.");
    }
}
