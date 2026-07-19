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
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show create user page.
     */
    public function create()
    {
        $objects = Obj::where('is_active', true)->get();
        return view('admin.users.create', compact('objects'));
    }

    /**
     * Store new user.
     */
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

                // Enforce 1:1 manager-object constraint
                $oldManagerAssignment = ObjectManager::where('object_id', $objectId)->first();
                if ($oldManagerAssignment) {
                    ObjectManagerHistory::where('object_id', $objectId)
                        ->whereNull('unassigned_at')
                        ->update([
                            'unassigned_at' => now(),
                            'reason' => 'Yangi menejer tayinlandi.'
                        ]);
                    $oldManagerAssignment->delete();
                }

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

    /**
     * Show edit user page.
     */
    public function edit(User $user)
    {
        $objects = Obj::where('is_active', true)->get();
        
        $assignedObject = ObjectManager::where('user_id', $user->id)->first();
        $user->object_id = $assignedObject ? $assignedObject->object_id : null;

        return view('admin.users.edit', compact('user', 'objects'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string',
            'pin_code' => 'nullable|required_if:role,financier|string|size:4|regex:/^\d{4}$/',
            'object_id' => 'nullable|required_if:role,manager|exists:objects,id',
        ]);

        $oldValues = $user->toArray();

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->role === 'financier' && $request->filled('pin_code')) {
            $userData['pin_code'] = Hash::make($request->pin_code);
        }

        DB::transaction(function () use ($user, $userData, $request, $oldValues) {
            $user->update($userData);

            // Handle manager object assignment
            $currentManager = ObjectManager::where('user_id', $user->id)->first();
            $newObjectId = $request->role === 'manager' && $request->filled('object_id') ? (int)$request->object_id : null;

            if ($request->role !== 'manager' || $newObjectId === null) {
                if ($currentManager) {
                    ObjectManagerHistory::where('object_id', $currentManager->object_id)
                        ->whereNull('unassigned_at')
                        ->update([
                            'unassigned_at' => now(),
                            'reason' => 'Menejer lavozimi o\'zgardi yoki olib tashlandi.'
                        ]);
                    $currentManager->delete();
                }
            } else {
                if (!$currentManager || ($currentManager->object_id !== $newObjectId)) {
                    if ($currentManager) {
                        ObjectManagerHistory::where('object_id', $currentManager->object_id)
                            ->whereNull('unassigned_at')
                            ->update([
                                'unassigned_at' => now(),
                                'reason' => 'Boshqa obyektga o\'tkazildi.'
                            ]);
                        $currentManager->delete();
                    }

                    $oldManagerAssignment = ObjectManager::where('object_id', $newObjectId)->first();
                    if ($oldManagerAssignment) {
                        ObjectManagerHistory::where('object_id', $newObjectId)
                            ->whereNull('unassigned_at')
                            ->update([
                                'unassigned_at' => now(),
                                'reason' => 'Yangi menejer tayinlandi.'
                            ]);
                        $oldManagerAssignment->delete();
                    }

                    ObjectManager::create([
                        'object_id' => $newObjectId,
                        'user_id' => $user->id,
                        'assigned_at' => now(),
                    ]);

                    ObjectManagerHistory::create([
                        'object_id' => $newObjectId,
                        'user_id' => $user->id,
                        'assigned_at' => now(),
                        'reason' => 'Menejer tayinlandi.'
                    ]);
                }
            }

            AuditLogger::log('update_user', $user, $oldValues, $user->toArray());
        });

        return redirect()->route('admin.users.index')->with('success', 'Foydalanuvchi muvaffaqiyatli tahrirlandi.');
    }

    /**
     * Delete user.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->withErrors(['error' => 'O\'z shaxsingizni o\'chira olmaysiz!']);
        }

        DB::transaction(function () use ($user) {
            $currentManager = ObjectManager::where('user_id', $user->id)->first();
            if ($currentManager) {
                ObjectManagerHistory::where('object_id', $currentManager->object_id)
                    ->whereNull('unassigned_at')
                    ->update([
                        'unassigned_at' => now(),
                        'reason' => 'Foydalanuvchi o\'chirilganligi sababli menejer olib tashlandi.'
                    ]);
                $currentManager->delete();
            }

            $user->delete();
            AuditLogger::log('delete_user', $user);
        });

        return redirect()->route('admin.users.index')->with('success', 'Foydalanuvchi muvaffaqiyatli o\'chirildi.');
    }

    /**
     * Toggle active state.
     */
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
