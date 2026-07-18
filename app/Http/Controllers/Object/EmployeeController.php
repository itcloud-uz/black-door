<?php

declare(strict_types=1);

namespace App\Http\Controllers\Object;

use App\Http\Controllers\Controller;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\User;
use App\Models\SalaryPayment;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\ObjectTransactionCategory;
use App\Models\ObjectTransaction;
use App\Enums\UserRole;
use App\Enums\Currency;
use App\Enums\TransactionType;
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

        $cashAccounts = ObjectCashAccount::with('balances')
            ->where('object_id', $object->id)
            ->where('is_active', true)
            ->get();

        $categories = ObjectTransactionCategory::where(function ($q) use ($object) {
                $q->where('object_id', $object->id)->orWhereNull('object_id');
            })
            ->where('is_active', true)
            ->get();

        $salaryPayments = SalaryPayment::with(['employee.user', 'creator'])
            ->where('object_id', $object->id)
            ->orderBy('paid_at', 'desc')
            ->paginate(15);

        return view('manager.employees.index', compact('object', 'employees', 'cashAccounts', 'categories', 'salaryPayments'));
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
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:warehouse,transactions,employees',
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
                'permissions' => $request->input('permissions', []),
                'is_active' => true,
            ]);

            AuditLogger::log('create_employee', $employee, null, $employee->toArray());
        });

        return redirect()->route('manager.employees.index')->with('success', 'Xodim muvaffaqiyatli qo\'shildi.');
    }

    public function update(Request $request, ObjectEmployee $employee)
    {
        $object = $this->getObject();
        if (!$object || $employee->object_id !== $object->id) {
            abort(404);
        }

        $request->validate([
            'position' => 'required|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'daily_rate_currency' => 'nullable|string',
            'monthly_rate' => 'nullable|numeric|min:0',
            'monthly_rate_currency' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:warehouse,transactions,employees',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->user_id,
            'phone' => 'nullable|string|max:20',
        ]);

        $oldValues = $employee->toArray();

        $dailyRate = $request->filled('daily_rate') ? (int)round((float)$request->daily_rate * 100) : 0;
        $monthlyRate = $request->filled('monthly_rate') ? (int)round((float)$request->monthly_rate * 100) : 0;

        DB::transaction(function () use ($request, $employee, $dailyRate, $monthlyRate) {
            $employee->update([
                'position' => $request->position,
                'daily_rate_currency' => $request->input('daily_rate_currency', Currency::UZS->value),
                'daily_rate' => $dailyRate,
                'monthly_rate_currency' => $request->input('monthly_rate_currency', Currency::UZS->value),
                'monthly_rate' => $monthlyRate,
                'permissions' => $request->input('permissions', []),
            ]);

            if ($employee->user) {
                $employee->user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);
            }
        });

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

    public function paySalary(Request $request, ObjectEmployee $employee)
    {
        $object = $this->getObject();
        if (!$object || $employee->object_id !== $object->id) {
            abort(404);
        }

        $request->validate([
            'object_cash_account_id' => 'required|exists:object_cash_accounts,id',
            'type' => 'required|string|in:salary,advance',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:UZS,USD',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $cashAccountId = (int)$request->object_cash_account_id;
        $currencyVal = $request->currency;
        $amountCents = (int)round((float)$request->amount * 100);

        try {
            DB::transaction(function () use ($request, $object, $employee, $cashAccountId, $currencyVal, $amountCents) {
                // 1. Get cash balance
                $cashBalance = ObjectCashBalance::where('object_cash_account_id', $cashAccountId)
                    ->where('currency', $currencyVal)
                    ->first();

                if (!$cashBalance || $cashBalance->balance < $amountCents) {
                    throw new \Exception('Kassada yetarli mablag\' mavjud emas.');
                }

                // 2. Deduct cash balance
                $newBalance = $cashBalance->balance - $amountCents;
                $cashBalance->balance = $newBalance;
                $cashBalance->save();

                // 3. Find/Create category for Ish haqi/Avans
                $categoryName = $request->type === 'salary' ? 'Ish haqi' : 'Avans';
                $category = ObjectTransactionCategory::where('name', $categoryName)
                    ->where('type', 'expense')
                    ->first();
                if (!$category) {
                    $category = ObjectTransactionCategory::create([
                        'object_id' => $object->id,
                        'name' => $categoryName,
                        'type' => 'expense',
                        'is_active' => true
                    ]);
                }

                // 4. Create ObjectTransaction
                $tx = ObjectTransaction::create([
                    'object_id' => $object->id,
                    'object_cash_account_id' => $cashAccountId,
                    'category_id' => $category->id,
                    'counterparty_name' => $employee->user->name,
                    'type' => TransactionType::Expense->value,
                    'currency' => $currencyVal,
                    'amount' => $amountCents,
                    'balance_after' => $newBalance,
                    'note' => $request->note ?? ($request->type === 'salary' ? 'Oylik to\'lovi' : 'Avans to\'lovi'),
                    'created_by' => Auth::id(),
                    'transaction_date' => now()->toDateString(),
                ]);

                // 5. Create SalaryPayment
                $payment = SalaryPayment::create([
                    'object_id' => $object->id,
                    'employee_id' => $employee->id,
                    'currency' => $currencyVal,
                    'amount' => $amountCents,
                    'period_start' => $request->period_start,
                    'period_end' => $request->period_end,
                    'note' => $request->note,
                    'paid_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                AuditLogger::log('create_salary_payment', $payment, null, $payment->toArray());
                AuditLogger::log('create_object_transaction', $tx, null, $tx->toArray());
            });

            return redirect()->route('manager.employees.index')->with('success', 'To\'lov muvaffaqiyatli amalga oshirildi.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
