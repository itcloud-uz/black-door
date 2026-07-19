<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Obj;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\User;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\ObjectTransaction;
use App\Models\ObjectTransactionCategory;
use App\Models\WarehouseStock;
use App\Models\WarehouseMovement;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Product;
use App\Models\SalaryPayment;
use App\Enums\WarehouseMovementType;
use App\Enums\TransactionType;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Services\AuditLogger;
use App\Services\AnalyticsClient;
use App\Events\TransactionCreated;
use App\Events\LowStockWarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ManagerController extends Controller
{
    /**
     * Helper to get assigned object for current manager/employee
     */
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

    /**
     * Dashboard Statistics
     */
    public function dashboard()
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        // Mini-cash balances
        $cashAccounts = ObjectCashAccount::where('object_id', $object->id)->where('is_active', true)->get();
        $balances = [
            'usd' => 0,
            'uzs' => 0
        ];
        $balancesDetailed = [];

        foreach ($cashAccounts as $acc) {
            $usd = $acc->getBalance(Currency::USD) / 100;
            $uzs = $acc->getBalance(Currency::UZS) / 100;
            $balances['usd'] += $usd;
            $balances['uzs'] += $uzs;
            $balancesDetailed[] = [
                'id' => $acc->id,
                'name' => $acc->name,
                'usd' => $usd,
                'uzs' => $uzs
            ];
        }

        $employeeCount = ObjectEmployee::where('object_id', $object->id)->where('is_active', true)->count();
        
        // Stock overview & Low stock warnings
        $stocks = WarehouseStock::with('product')
            ->where('object_id', $object->id)
            ->get()
            ->map(function($st) {
                $isLow = $st->quantity < ($st->product->min_limit ?? 0);
                return [
                    'product_id' => $st->product_id,
                    'name' => $st->product->name,
                    'quantity' => $st->quantity,
                    'unit' => $st->product->unit->value,
                    'min_limit' => $st->product->min_limit ?? 0,
                    'is_low' => $isLow
                ];
            });

        $lowStockWarnings = $stocks->filter(fn($st) => $st['is_low'])->values();

        return response()->json([
            'object' => [
                'id' => $object->id,
                'name' => $object->name,
                'type' => $object->type->value,
            ],
            'balances' => $balances,
            'balances_detailed' => $balancesDetailed,
            'employee_count' => $employeeCount,
            'stocks' => $stocks,
            'low_stock_warnings' => $lowStockWarnings
        ]);
    }

    /**
     * List Employees
     */
    public function listEmployees()
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $employees = ObjectEmployee::with('user')
            ->where('object_id', $object->id)
            ->get()
            ->map(function($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->user->name,
                    'phone' => $emp->user->phone,
                    'position' => $emp->position,
                    'daily_rate' => $emp->daily_rate / 100,
                    'daily_rate_currency' => $emp->daily_rate_currency?->value,
                    'monthly_rate' => $emp->monthly_rate / 100,
                    'monthly_rate_currency' => $emp->monthly_rate_currency?->value,
                    'is_active' => $emp->is_active,
                ];
            });

        return response()->json($employees);
    }

    /**
     * Store Employee
     */
    public function storeEmployee(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
            'position' => 'required|string',
            'daily_rate' => 'nullable|numeric|min:0',
            'daily_rate_currency' => 'nullable|string',
            'monthly_rate' => 'nullable|numeric|min:0',
            'monthly_rate_currency' => 'nullable|string',
        ]);

        $employee = DB::transaction(function () use ($request, $object) {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => UserRole::Employee->value,
                'is_active' => true,
            ]);

            // Create employee record
            $emp = ObjectEmployee::create([
                'object_id' => $object->id,
                'user_id' => $user->id,
                'position' => $request->position,
                'daily_rate' => $request->daily_rate ? (int)round($request->daily_rate * 100) : 0,
                'daily_rate_currency' => $request->daily_rate_currency,
                'monthly_rate' => $request->monthly_rate ? (int)round($request->monthly_rate * 100) : 0,
                'monthly_rate_currency' => $request->monthly_rate_currency,
                'hired_at' => now()->toDateString(),
                'is_active' => true,
            ]);

            return $emp;
        });

        AuditLogger::log('create_employee', $employee, null, $employee->toArray());

        return response()->json([
            'message' => 'Xodim muvaffaqiyatli qo\'shildi.',
            'employee' => $employee
        ], 201);
    }

    public function updateEmployee(Request $request, ObjectEmployee $employee)
    {
        $request->validate([
            'position' => 'required|string',
            'daily_rate' => 'nullable|numeric|min:0',
            'daily_rate_currency' => 'nullable|string',
            'monthly_rate' => 'nullable|numeric|min:0',
            'monthly_rate_currency' => 'nullable|string',
        ]);

        $oldData = $employee->toArray();

        $employee->update([
            'position' => $request->position,
            'daily_rate' => $request->daily_rate ? (int)round($request->daily_rate * 100) : 0,
            'daily_rate_currency' => $request->daily_rate_currency,
            'monthly_rate' => $request->monthly_rate ? (int)round($request->monthly_rate * 100) : 0,
            'monthly_rate_currency' => $request->monthly_rate_currency,
        ]);

        AuditLogger::log('update_employee', $employee, $oldData, $employee->toArray());

        return response()->json([
            'message' => 'Xodim ma\'lumotlari yangilandi.',
            'employee' => $employee
        ]);
    }

    public function toggleActiveEmployee(ObjectEmployee $employee)
    {
        $oldData = $employee->toArray();
        $employee->update(['is_active' => !$employee->is_active]);

        // Toggle user as well
        $employee->user->update(['is_active' => $employee->is_active]);

        AuditLogger::log('toggle_employee_active', $employee, $oldData, $employee->toArray());

        return response()->json([
            'message' => 'Xodim holati o\'zgartirildi.',
            'employee' => $employee
        ]);
    }

    /**
     * Pay Salary or Advance
     */
    public function paySalary(Request $request, ObjectEmployee $employee)
    {
        $object = $this->getObject();
        if (!$object || $employee->object_id !== $object->id) {
            return response()->json(['message' => 'Obyekt ruxsati yo\'q.'], 403);
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
                $cashBalance = ObjectCashBalance::where('object_cash_account_id', $cashAccountId)
                    ->where('currency', $currencyVal)
                    ->first();

                if (!$cashBalance || $cashBalance->balance < $amountCents) {
                    throw new \Exception('Kassada yetarli mablag\' mavjud emas.');
                }

                $newBalance = $cashBalance->balance - $amountCents;
                $cashBalance->balance = $newBalance;
                $cashBalance->save();

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

            return response()->json(['message' => 'To\'lov muvaffaqiyatli amalga oshirildi.']);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * List Mini-Cash Transactions
     */
    public function listTransactions(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $query = ObjectTransaction::with(['cashAccount', 'category'])
            ->where('object_id', $object->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(30);

        return response()->json($transactions);
    }

    /**
     * Store Mini-Cash Transaction
     */
    public function storeTransaction(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $request->validate([
            'object_cash_account_id' => 'required|exists:object_cash_accounts,id',
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'category_id' => 'nullable|exists:object_transaction_categories,id',
            'counterparty_name' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $type = $request->type;
        $amountVal = (float)$request->amount;
        $amountCents = (int)round($amountVal * 100);
        $currencyVal = $request->currency;
        $cashAccountId = (int)$request->object_cash_account_id;

        try {
            $tx = DB::transaction(function () use ($request, $object, $type, $amountCents, $currencyVal, $cashAccountId) {
                $cashBalance = ObjectCashBalance::where('object_cash_account_id', $cashAccountId)
                    ->where('currency', $currencyVal)
                    ->firstOrCreate([
                        'object_cash_account_id' => $cashAccountId,
                        'currency' => $currencyVal,
                    ], ['balance' => 0]);

                if ($type === 'income') {
                    $newBalance = $cashBalance->balance + $amountCents;
                    $cashBalance->balance = $newBalance;
                    $cashBalance->save();
                } else {
                    if ($cashBalance->balance < $amountCents) {
                        throw new \Exception('Kassada yetarli mablag\' mavjud emas.');
                    }
                    $newBalance = $cashBalance->balance - $amountCents;
                    $cashBalance->balance = $newBalance;
                    $cashBalance->save();
                }

                $transaction = ObjectTransaction::create([
                    'object_id' => $object->id,
                    'object_cash_account_id' => $cashAccountId,
                    'category_id' => $request->category_id,
                    'counterparty_name' => $request->counterparty_name,
                    'type' => $type === 'income' ? TransactionType::Income->value : TransactionType::Expense->value,
                    'currency' => $currencyVal,
                    'amount' => $amountCents,
                    'balance_after' => $newBalance,
                    'note' => $request->note,
                    'created_by' => Auth::id(),
                    'transaction_date' => now()->toDateString(),
                ]);

                AuditLogger::log('create_object_transaction', $transaction, null, $transaction->toArray());

                return $transaction;
            });

            return response()->json([
                'message' => 'Tranzaksiya muvaffaqiyatli qo\'shildi.',
                'transaction' => $tx
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * List Warehouse stocks
     */
    public function listStocks()
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $stocks = WarehouseStock::with('product')
            ->where('object_id', $object->id)
            ->get();

        return response()->json($stocks);
    }

    /**
     * Store Warehouse Movement
     */
    public function storeMovement(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|string|in:incoming,outgoing,transfer',
            'quantity' => 'required|integer|min:1',
            'to_object_id' => 'nullable|required_if:type,transfer|exists:objects,id',
            'recipient_name' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $productId = (int)$request->product_id;
        $type = $request->type;
        $qty = (int)$request->quantity;

        try {
            DB::transaction(function () use ($request, $object, $productId, $type, $qty) {
                $stock = WarehouseStock::where('object_id', $object->id)
                    ->where('product_id', $productId)
                    ->firstOrCreate([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                    ], ['quantity' => 0]);

                $product = Product::findOrFail($productId);

                if ($type === 'incoming') {
                    $stock->quantity += $qty;
                    $stock->save();

                    $mvt = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Incoming->value,
                        'quantity' => $qty,
                        'note' => $request->note,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_incoming', $mvt, null, $mvt->toArray());

                } elseif ($type === 'outgoing') {
                    if ($stock->quantity < $qty) {
                        throw new \Exception('Omborda yetarli mahsulot mavjud emas.');
                    }

                    $stock->quantity -= $qty;
                    $stock->save();

                    $mvt = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Outgoing->value,
                        'quantity' => $qty,
                        'note' => $request->note,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_outgoing', $mvt, null, $mvt->toArray());

                    // Check for low stock alert
                    if ($stock->quantity < ($product->min_limit ?? 0)) {
                        broadcast(new LowStockWarning([
                            'object_name' => $object->name,
                            'product_name' => $product->name,
                            'quantity' => $stock->quantity,
                            'min_limit' => $product->min_limit
                        ]))->toOthers();
                    }

                } elseif ($type === 'transfer') {
                    $toObjectId = (int)$request->to_object_id;
                    if ($toObjectId === $object->id) {
                        throw new \Exception('O\'tkazish uchun boshqa obyekt tanlanishi kerak.');
                    }

                    if ($stock->quantity < $qty) {
                        throw new \Exception('Omborda yetarli mahsulot mavjud emas.');
                    }

                    $toObject = Obj::findOrFail($toObjectId);
                    $destStock = WarehouseStock::where('object_id', $toObjectId)
                        ->where('product_id', $productId)
                        ->firstOrCreate([
                            'object_id' => $toObjectId,
                            'product_id' => $productId,
                        ], ['quantity' => 0]);

                    // Deduct source
                    $stock->quantity -= $qty;
                    $stock->save();

                    // Add destination
                    $destStock->quantity += $qty;
                    $destStock->save();

                    // Log movements
                    $mvtOut = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Transfer->value,
                        'quantity' => $qty,
                        'from_object_id' => $object->id,
                        'to_object_id' => $toObjectId,
                        'note' => $request->note ?? "O'tkazildi: " . $toObject->name,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    $mvtIn = WarehouseMovement::create([
                        'object_id' => $toObjectId,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Incoming->value,
                        'quantity' => $qty,
                        'from_object_id' => $object->id,
                        'to_object_id' => $toObjectId,
                        'note' => $request->note ?? "O'tkazib olindi: " . $object->name,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_transfer', $mvtOut, null, [
                        'source' => $mvtOut->toArray(),
                        'destination' => $mvtIn->toArray()
                    ]);

                    // Check for low stock alert
                    if ($stock->quantity < ($product->min_limit ?? 0)) {
                        broadcast(new LowStockWarning([
                            'object_name' => $object->name,
                            'product_name' => $product->name,
                            'quantity' => $stock->quantity,
                            'min_limit' => $product->min_limit
                        ]))->toOthers();
                    }
                }
            });

            return response()->json([
                'message' => 'Ombor harakati muvaffaqiyatli yozildi.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store Inventory Check
     */
    public function storeInventoryCheck(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $request->validate([
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.actual_qty' => 'required|integer|min:0',
            'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $object) {
                $check = InventoryCheck::create([
                    'object_id' => $object->id,
                    'checked_by' => Auth::id(),
                    'checked_at' => now(),
                    'status' => 'approved',
                    'note' => $request->input('note', 'Tizimli inventarizatsiya (mobil).'),
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                foreach ($request->items as $itemData) {
                    $productId = (int)$itemData['product_id'];
                    $actualQty = (int)$itemData['actual_qty'];

                    $stock = WarehouseStock::where('object_id', $object->id)
                        ->where('product_id', $productId)
                        ->firstOrCreate([
                            'object_id' => $object->id,
                            'product_id' => $productId,
                        ], ['quantity' => 0]);

                    $expectedQty = $stock->quantity;
                    $diff = $actualQty - $expectedQty;

                    InventoryCheckItem::create([
                        'inventory_check_id' => $check->id,
                        'product_id' => $productId,
                        'expected_qty' => $expectedQty,
                        'actual_qty' => $actualQty,
                        'difference' => $diff,
                        'note' => $itemData['note'] ?? null,
                    ]);

                    $stock->quantity = $actualQty;
                    $stock->save();

                    if ($diff !== 0) {
                        WarehouseMovement::create([
                            'object_id' => $object->id,
                            'product_id' => $productId,
                            'type' => WarehouseMovementType::InventoryAdjustment->value,
                            'quantity' => abs($diff),
                            'note' => "Inventarizatsiya farqi: " . ($diff > 0 ? "+{$diff}" : "{$diff}"),
                            'created_by' => Auth::id(),
                            'movement_date' => now()->toDateString(),
                        ]);
                    }
                }

                AuditLogger::log('inventory_check', $check, null, $check->toArray());
            });

            return response()->json([
                'message' => 'Inventarizatsiya muvaffaqiyatli yakunlandi va qoldiqlar yangilandi.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Daily Summary
     */
    public function dailySummary(Request $request)
    {
        $object = $this->getObject();
        if (!$object) {
            return response()->json(['message' => 'Obyekt biriktirilmagan.'], 403);
        }

        $date = $request->input('date', now()->toDateString());
        $client = new AnalyticsClient();

        try {
            $summary = $client->getObjectDailyReport($object->id, $date);
            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kunlik yakun hisoboti yuklashda xatolik: ' . $e->getMessage()
            ], 400);
        }
    }
}
