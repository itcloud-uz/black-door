<?php

declare(strict_types=1);

namespace App\Http\Controllers\Object;

use App\Http\Controllers\Controller;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\User;
use App\Models\SalaryPayment;
use App\Enums\UserRole;
use App\Enums\Currency;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    protected function getObject()
    {
        $user = Auth::user();
        if ($user->role->value === 'manager') {
            $mgr = ObjectManager::where('user_id', $user->id)->first();
            return $mgr ? $mgr->object : null;
        }
        $emp = ObjectEmployee::where('user_id', $user->id)->first();
        return $emp ? $emp->object : null;
    }

    public function index()
    {
        $object = $this->getObject();
        if (! $object) {
            return redirect()->route('manager.dashboard')->withErrors(['error' => 'Obyekt biriktirilmagan.']);
        }

        $employees = ObjectEmployee::with(['user', 'salaryPayments'])
            ->where('object_id', $object->id)
            ->get();

        return view('manager.employees.index', compact('object', 'employees'));
    }

    public function store(Request $request)
    {
        $object = $this->getObject();
        if (! $object) {
            return back()->withErrors(['error' => 'Obyekt topilmadi.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'position' => 'required|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'daily_rate_currency' => 'nullable|string',
            'monthly_rate' => 'nullable|numeric|min:0',
            'monthly_rate_currency' => 'nullable|string',
            'hired_at' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $object) {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => UserRole::Employee->value,
                'is_active' => true,
            ]);

            // Rates
            $dailyRate = $request->filled('daily_rate') ? (int)round((float)$request->daily_rate * 100) : 0;
            $monthlyRate = $request->filled('monthly_rate') ? (int)round((float)$request->monthly_rate * 100) : 0;

            $employee = ObjectEmployee::create([
                'object_id' => $object->id,
                'user_id' => $user->id,
                'position' => $request->position,
                'daily_rate_currency' => $request->input('daily_rate_currency', Currency::UZS->value),
                'daily_rate' => $dailyRate,
                'monthly_rate_currency' => $request->input('monthly_rate_currency', Currency::UZS->value),
                'monthly_rate' => $monthlyRate,
                'hired_at' => $request->hired_at,
                'is_active' => true,
            ]);

            AuditLogger::log('create_employee', $employee, null, $employee->toArray());
        });

        return redirect()->route('manager.employees.index')->with('success', 'Xodim muvaffaqiyatli qo\'shildi.');
    }

    public function update(Request $request, ObjectEmployee $employee)
    {
        $request->validate([
            'position' => 'required|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'daily_rate_currency' => 'nullable|string',
            'monthly_rate' => 'nullable|numeric|min:0',
            'monthly_rate_currency' => 'nullable|string',
        ]);

        $oldValues = $employee->toArray();

        $dailyRate = $request->filled('daily_rate') ? (int)round((float)$request->daily_rate * 100) : 0;
        $monthlyRate = $request->filled('monthly_rate') ? (int)round((float)$request->monthly_rate * 100) : 0;

        $employee->update([
            'position' => $request->position,
            'daily_rate_currency' => $request->input('daily_rate_currency', Currency::UZS->value),
            'daily_rate' => $dailyRate,
            'monthly_rate_currency' => $request->input('monthly_rate_currency', Currency::UZS->value),
            'monthly_rate' => $monthlyRate,
        ]);

        AuditLogger::log('update_employee', $employee, $oldValues, $employee->toArray());

        return redirect()->route('manager.employees.index')->with('success', 'Xodim ma\'lumotlari muvaffaqiyatli yangilandi.');
    }

    public function toggleActive(ObjectEmployee $employee)
    {
        $oldValues = $employee->toArray();
        $employee->is_active = ! $employee->is_active;
        $employee->save();

        // Also toggle the active status of the linked user
        $user = $employee->user;
        if ($user) {
            $user->is_active = $employee->is_active;
            $user->save();
        }

        AuditLogger::log('toggle_employee_status', $employee, $oldValues, $employee->toArray());

        $statusStr = $employee->is_active ? 'faollashtirildi' : 'faolsizlantirildi';
        return redirect()->route('manager.employees.index')->with('success', "Xodim muvaffaqiyatli {$statusStr}.");
    }
}
