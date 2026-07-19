<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Obj;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Transaction;
use App\Models\CurrencyRate;
use App\Models\AuditLog;
use App\Models\ObjectManager;
use App\Models\Product;
use App\Enums\UserRole;
use App\Enums\ObjectType;
use App\Enums\Currency;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Dashboard Statistics
     */
    public function dashboard()
    {
        // Total cash by currency
        $totalUsd = (int) CashBalance::where('currency', Currency::USD->value)->sum('balance');
        $totalUzs = (int) CashBalance::where('currency', Currency::UZS->value)->sum('balance');

        $objectsCount = Obj::where('is_active', true)->count();
        $usersCount = User::where('is_active', true)->count();

        // Cash accounts list with balances
        $cashAccounts = CashAccount::with('balances')->get()->map(function($acc) {
            return [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type->value,
                'note' => $acc->note,
                'is_active' => $acc->is_active,
                'usd_balance' => $acc->usdBalance() / 100,
                'uzs_balance' => $acc->uzsBalance() / 100,
            ];
        });

        // Recent transactions (last 10)
        $recentTransactions = Transaction::with(['cashAccount', 'category', 'counterparty'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($tx) {
                return [
                    'id' => $tx->id,
                    'cash_account' => $tx->cashAccount->name ?? null,
                    'counterparty' => $tx->counterparty->name ?? null,
                    'category' => $tx->category->name ?? null,
                    'type' => $tx->type->value,
                    'currency' => $tx->currency->value,
                    'amount' => $tx->amount / 100,
                    'note' => $tx->note,
                    'created_at' => $tx->created_at->toDateTimeString(),
                ];
            });

        // Current currency rate
        $latestRateModel = CurrencyRate::latest()->first();
        $currentRate = $latestRateModel ? $latestRateModel->rate_uzs_per_usd / 100 : 0;

        // Recent audit logs (last 10)
        $recentAuditLogs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user->name ?? 'System',
                    'action' => $log->action,
                    'auditable_type' => $log->auditable_type,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'totals' => [
                'usd' => $totalUsd / 100,
                'uzs' => $totalUzs / 100,
            ],
            'counts' => [
                'objects' => $objectsCount,
                'users' => $usersCount,
            ],
            'cash_accounts' => $cashAccounts,
            'recent_transactions' => $recentTransactions,
            'current_rate' => $currentRate,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }

    /**
     * Users Management
     */
    public function listUsers(Request $request)
    {
        $users = User::orderBy('name')->paginate(30);
        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['super_admin', 'financier', 'manager', 'employee'])],
            'pin_code' => 'nullable|string|size:4',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'pin_code' => $request->pin_code ? Hash::make($request->pin_code) : null,
            'is_active' => $request->is_active ?? true,
        ]);

        AuditLogger::log('create_user', $user, null, $user->toArray());

        return response()->json([
            'message' => 'Foydalanuvchi muvaffaqiyatli yaratildi.',
            'user' => $user
        ], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in(['super_admin', 'financier', 'manager', 'employee'])],
            'pin_code' => 'nullable|string|size:4',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->is_active ?? $user->is_active,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->filled('pin_code')) {
            $data['pin_code'] = Hash::make($request->pin_code);
        }

        $oldData = $user->toArray();
        $user->update($data);

        AuditLogger::log('update_user', $user, $oldData, $user->toArray());

        return response()->json([
            'message' => 'Foydalanuvchi muvaffaqiyatli yangilandi.',
            'user' => $user
        ]);
    }

    public function toggleActiveUser(User $user)
    {
        $oldData = $user->toArray();
        $user->update(['is_active' => !$user->is_active]);

        AuditLogger::log('toggle_user_active', $user, $oldData, $user->toArray());

        return response()->json([
            'message' => 'Foydalanuvchi holati o\'zgartirildi.',
            'user' => $user
        ]);
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'O\'zingizni o\'chira olmaysiz.'], 403);
        }

        $oldData = $user->toArray();
        $user->delete();

        AuditLogger::log('delete_user', $user, $oldData, null);

        return response()->json(['message' => 'Foydalanuvchi muvaffaqiyatli o\'chirildi.']);
    }

    /**
     * Objects Management
     */
    public function listObjects()
    {
        $objects = Obj::with(['manager'])->get()->map(function($object) {
            return [
                'id' => $object->id,
                'name' => $object->name,
                'type' => $object->type->value,
                'address' => $object->address,
                'note' => $object->note,
                'is_active' => $object->is_active,
                'manager_name' => $object->manager->name ?? 'Tayinlanmagan',
                'manager_id' => $object->manager->id ?? null,
            ];
        });

        return response()->json($objects);
    }

    public function storeObject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['factory', 'construction', 'warehouse'])],
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $object = DB::transaction(function () use ($request) {
            $obj = Obj::create([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'note' => $request->note,
                'is_active' => true,
            ]);

            if ($request->manager_id) {
                ObjectManager::create([
                    'object_id' => $obj->id,
                    'user_id' => $request->manager_id,
                    'assigned_at' => now(),
                ]);
            }

            return $obj;
        });

        AuditLogger::log('create_object', $object, null, $object->toArray());

        return response()->json([
            'message' => 'Obyekt muvaffaqiyatli yaratildi.',
            'object' => $object
        ], 201);
    }

    public function updateObject(Request $request, Obj $object)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['factory', 'construction', 'warehouse'])],
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $oldData = $object->toArray();

        DB::transaction(function () use ($request, $object) {
            $object->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'note' => $request->note,
            ]);

            if ($request->filled('manager_id')) {
                // Remove old manager if any
                ObjectManager::where('object_id', $object->id)->delete();

                ObjectManager::create([
                    'object_id' => $object->id,
                    'user_id' => $request->manager_id,
                    'assigned_at' => now(),
                ]);
            }
        });

        AuditLogger::log('update_object', $object, $oldData, $object->toArray());

        return response()->json([
            'message' => 'Obyekt muvaffaqiyatli yangilandi.',
            'object' => $object
        ]);
    }

    public function destroyObject(Obj $object)
    {
        $oldData = $object->toArray();
        $object->delete();

        AuditLogger::log('delete_object', $object, $oldData, null);

        return response()->json(['message' => 'Obyekt muvaffaqiyatli o\'chirildi.']);
    }

    /**
     * Products Management
     */
    public function listProducts()
    {
        $products = Product::orderBy('name')->get();
        return response()->json($products);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string',
            'min_limit' => 'nullable|integer|min:0',
        ]);

        $product = Product::create($request->only(['name', 'unit', 'min_limit']) + ['is_active' => true]);

        AuditLogger::log('create_product', $product, null, $product->toArray());

        return response()->json([
            'message' => 'Mahsulot muvaffaqiyatli yaratildi.',
            'product' => $product
        ], 201);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string',
            'min_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $oldData = $product->toArray();
        $product->update($request->only(['name', 'unit', 'min_limit', 'is_active']));

        AuditLogger::log('update_product', $product, $oldData, $product->toArray());

        return response()->json([
            'message' => 'Mahsulot muvaffaqiyatli yangilandi.',
            'product' => $product
        ]);
    }

    public function destroyProduct(Product $product)
    {
        $oldData = $product->toArray();
        $product->delete();

        AuditLogger::log('delete_product', $product, $oldData, null);

        return response()->json(['message' => 'Mahsulot muvaffaqiyatli o\'chirildi.']);
    }

    /**
     * Dangerous: Hard Delete Transaction (Super Admin only)
     */
    public function destroyTransaction(Transaction $transaction)
    {
        $oldData = $transaction->toArray();

        // Reverse balance before deleting
        DB::transaction(function() use ($transaction, $oldData) {
            $cashBalance = CashBalance::where('cash_account_id', $transaction->cash_account_id)
                ->where('currency', $transaction->currency->value)
                ->first();

            if ($cashBalance) {
                // If it was income, subtract. If expense, add back.
                if ($transaction->type->value === 'income' || $transaction->type->value === 'transfer_in') {
                    $cashBalance->balance -= $transaction->amount;
                } else {
                    $cashBalance->balance += $transaction->amount;
                }
                $cashBalance->save();
            }

            $transaction->delete();
        });

        AuditLogger::log('hard_delete_transaction', $transaction, $oldData, null);

        return response()->json(['message' => 'Tranzaksiya butunlay o\'chirildi va balanslar tiklandi.']);
    }

    /**
     * Currency Rates
     */
    public function getCurrencyRates()
    {
        $rates = CurrencyRate::with('setter')->latest()->paginate(30);
        return response()->json($rates);
    }

    public function storeCurrencyRate(Request $request)
    {
        $request->validate([
            'rate_uzs_per_usd' => 'required|numeric|min:1',
            'note' => 'nullable|string',
        ]);

        $rateVal = (float)$request->rate_uzs_per_usd;
        $rateCents = (int)round($rateVal * 100);

        $today = now()->toDateString();
        $rate = CurrencyRate::whereDate('effective_date', $today)->first();
        if ($rate) {
            $rate->update([
                'rate_uzs_per_usd' => $rateCents,
                'set_by' => auth()->id(),
                'note' => $request->note,
            ]);
        } else {
            $rate = CurrencyRate::create([
                'rate_uzs_per_usd' => $rateCents,
                'set_by' => auth()->id(),
                'effective_date' => $today,
                'note' => $request->note,
            ]);
        }

        AuditLogger::log('create_currency_rate', $rate, null, $rate->toArray());

        return response()->json([
            'message' => 'Valyuta kursi muvaffaqiyatli o\'rnatildi.',
            'rate' => $rateVal
        ], 201);
    }

    /**
     * Audit Log
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);
        return response()->json($logs);
    }

    public function fetchCbuRate()
    {
        $rate = \App\Services\CbuCurrencyService::fetchCbuUsdRate();
        if ($rate !== null) {
            return response()->json([
                'success' => true,
                'rate' => $rate
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Markaziy bank kursini olib bo\'lmadi.'
        ], 500);
    }
}
