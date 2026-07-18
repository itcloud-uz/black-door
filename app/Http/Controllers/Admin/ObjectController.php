<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obj;
use App\Models\User;
use App\Models\ObjectManager;
use App\Models\ObjectManagerHistory;
use App\Enums\UserRole;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObjectController extends Controller
{
    public function index()
    {
        $objects = Obj::with(['activeManager.user'])->orderBy('name')->get();
        return view('admin.objects.index', compact('objects'));
    }

    public function create()
    {
        // Get active managers
        $managers = User::where('role', UserRole::Manager->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('admin.objects.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'address' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $object = Obj::create([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'note' => $request->note,
                'is_active' => true,
            ]);

            AuditLogger::log('create_object', $object, null, $object->toArray());

            if ($request->filled('manager_id')) {
                $managerId = (int)$request->manager_id;

                // Enforce 1:1:
                // Check if this manager is already assigned to some object, unassign them first!
                $existingManagerAssignment = ObjectManager::where('user_id', $managerId)->first();
                if ($existingManagerAssignment) {
                    ObjectManagerHistory::where('object_id', $existingManagerAssignment->object_id)
                        ->whereNull('unassigned_at')
                        ->update([
                            'unassigned_at' => now(),
                            'reason' => 'Menejer boshqa obyektga o\'tkazildi.'
                        ]);
                    $existingManagerAssignment->delete();
                }

                // Create new assignment
                ObjectManager::create([
                    'object_id' => $object->id,
                    'user_id' => $managerId,
                    'assigned_at' => now(),
                ]);

                ObjectManagerHistory::create([
                    'object_id' => $object->id,
                    'user_id' => $managerId,
                    'assigned_at' => now(),
                    'reason' => 'Menejer tayinlandi.'
                ]);
            }
        });

        return redirect()->route('admin.objects.index')->with('success', 'Obyekt muvaffaqiyatli yaratildi.');
    }
}
