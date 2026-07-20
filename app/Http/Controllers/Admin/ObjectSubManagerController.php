<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obj;
use App\Models\ObjectSubManager;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ObjectSubManagerController extends Controller
{
    public function store(Request $request, Obj $object)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $sub = ObjectSubManager::create([
            'object_id' => $object->id,
            'user_id' => (int)$request->user_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        AuditLogger::log('assign_sub_manager', $sub, null, $sub->toArray());

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Vaqtinchalik o\'rinbosar muvaffaqiyatli tayinlandi.');
    }

    public function destroy(Obj $object, ObjectSubManager $subManager)
    {
        if ((int)$subManager->object_id !== (int)$object->id) {
            abort(404);
        }

        $subManagerData = $subManager->toArray();
        $subManager->delete();

        AuditLogger::log('remove_sub_manager', $subManager, null, $subManagerData);

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Vaqtinchalik o\'rinbosarlik bekor qilindi.');
    }
}
