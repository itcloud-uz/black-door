<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obj;
use App\Models\User;
use App\Models\ObjectManager;
use App\Models\ObjectManagerHistory;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\WarehouseStock;
use App\Models\WarehouseMovement;
use App\Models\Product;
use App\Models\ObjectEmployee;
use App\Enums\UserRole;
use App\Enums\CashAccountType;
use App\Enums\WarehouseMovementType;
use App\Enums\Currency;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ObjectController extends Controller
{
    /**
     * List all objects.
     */
    public function index()
    {
        $objects = Obj::with(['activeManager.user'])->orderBy('name')->get();
        return view('admin.objects.index', compact('objects'));
    }

    /**
     * Show create object page.
     */
    public function create()
    {
        $managers = User::where('role', UserRole::Manager->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('admin.objects.create', compact('managers'));
    }

    /**
     * Store new object.
     */
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

                // Enforce 1:1 manager assignment:
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

    /**
     * Show object details & reporting.
     */
    public function show(Obj $object)
    {
        $object->load([
            'activeManager.user',
            'cashAccounts.balances',
            'transactions.cashAccount',
            'transactions.category',
            'warehouseStocks.product',
            'employees.user',
            'subManagers.user'
        ]);

        // Calculate total balances across all object cash accounts
        $totalUZS = 0;
        $totalUSD = 0;
        foreach ($object->cashAccounts as $account) {
            $totalUZS += $account->getBalance(Currency::UZS);
            $totalUSD += $account->getBalance(Currency::USD);
        }

        // Available items for dropdown selectors
        $availableProducts = Product::where('is_active', true)->orderBy('name')->get();
        $availableEmployees = User::where('role', UserRole::Employee->value)
            ->where('is_active', true)
            ->whereNotIn('id', $object->employees->pluck('user_id')->toArray())
            ->orderBy('name')
            ->get();
        $availableManagers = User::where('role', UserRole::Manager->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.objects.show', compact('object', 'totalUZS', 'totalUSD', 'availableProducts', 'availableEmployees', 'availableManagers'));
    }

    /**
     * Show edit object page.
     */
    public function edit(Obj $object)
    {
        $managers = User::where('role', UserRole::Manager->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.objects.edit', compact('object', 'managers'));
    }

    /**
     * Update object.
     */
    public function update(Request $request, Obj $object)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'address' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request, $object) {
            $oldData = $object->toArray();
            
            $object->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'note' => $request->note,
                'is_active' => (bool)$request->is_active,
            ]);

            AuditLogger::log('update_object', $object, null, [
                'old' => $oldData,
                'new' => $object->toArray(),
            ]);

            $currentManager = ObjectManager::where('object_id', $object->id)->first();
            $newManagerId = $request->filled('manager_id') ? (int)$request->manager_id : null;

            if (($currentManager?->user_id) !== $newManagerId) {
                // Unassign old manager
                if ($currentManager) {
                    ObjectManagerHistory::where('object_id', $object->id)
                        ->whereNull('unassigned_at')
                        ->update([
                            'unassigned_at' => now(),
                            'reason' => 'Obyekt tahrirlanishi sababli menejer olib tashlandi.'
                        ]);
                    $currentManager->delete();
                }

                // Assign new manager
                if ($newManagerId) {
                    // Check 1:1
                    $existingAssignment = ObjectManager::where('user_id', $newManagerId)->first();
                    if ($existingAssignment) {
                        ObjectManagerHistory::where('object_id', $existingAssignment->object_id)
                            ->whereNull('unassigned_at')
                            ->update([
                                'unassigned_at' => now(),
                                'reason' => 'Menejer boshqa obyektga o\'tkazildi.'
                            ]);
                        $existingAssignment->delete();
                    }

                    ObjectManager::create([
                        'object_id' => $object->id,
                        'user_id' => $newManagerId,
                        'assigned_at' => now(),
                    ]);

                    ObjectManagerHistory::create([
                        'object_id' => $object->id,
                        'user_id' => $newManagerId,
                        'assigned_at' => now(),
                        'reason' => 'Menejer obyekt tahrirlanganda tayinlandi.'
                    ]);
                }
            }
        });

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Obyekt muvaffaqiyatli yangilandi.');
    }

    /**
     * Delete object.
     */
    public function destroy(Obj $object)
    {
        DB::transaction(function () use ($object) {
            // Unassign current manager
            $currentManager = ObjectManager::where('object_id', $object->id)->first();
            if ($currentManager) {
                ObjectManagerHistory::where('object_id', $object->id)
                    ->whereNull('unassigned_at')
                    ->update([
                        'unassigned_at' => now(),
                        'reason' => 'Obyekt o\'chirilganligi sababli menejer olib tashlandi.'
                    ]);
                $currentManager->delete();
            }

            $object->delete();
            AuditLogger::log('delete_object', $object);
        });

        return redirect()->route('admin.objects.index')->with('success', 'Obyekt o\'chirildi.');
    }

    /**
     * Add Cash Account to Object.
     */
    public function storeCashAccount(Request $request, Obj $object)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:cash,bank,card,safe,other',
        ]);

        $cashAccount = ObjectCashAccount::create([
            'object_id' => $object->id,
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => true,
        ]);

        // Initialize empty balances in both currencies
        ObjectCashBalance::create([
            'object_cash_account_id' => $cashAccount->id,
            'currency' => Currency::USD->value,
            'balance' => 0,
        ]);

        ObjectCashBalance::create([
            'object_cash_account_id' => $cashAccount->id,
            'currency' => Currency::UZS->value,
            'balance' => 0,
        ]);

        AuditLogger::log('create_object_cash_account', $cashAccount, null, $cashAccount->toArray());

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Yangi kassa muvaffaqiyatli qo\'shildi.');
    }

    /**
     * Delete Cash Account from Object.
     */
    public function destroyCashAccount(Obj $object, ObjectCashAccount $cashAccount)
    {
        $cashAccount->delete();
        AuditLogger::log('delete_object_cash_account', $cashAccount);
        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Kassa o\'chirildi.');
    }

    /**
     * Add/Adjust Warehouse Stock for Object.
     */
    public function storeWarehouseStock(Request $request, Obj $object)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = (int)$request->product_id;
        $qty = (int)$request->quantity;

        DB::transaction(function () use ($object, $productId, $qty) {
            $stock = WarehouseStock::where('object_id', $object->id)
                ->where('product_id', $productId)
                ->first();

            if ($stock) {
                $stock->increment('quantity', $qty);
            } else {
                $stock = WarehouseStock::create([
                    'object_id' => $object->id,
                    'product_id' => $productId,
                    'quantity' => $qty,
                ]);
            }

            // Record Warehouse Movement log
            WarehouseMovement::create([
                'object_id' => $object->id,
                'product_id' => $productId,
                'type' => WarehouseMovementType::Incoming->value,
                'quantity' => $qty,
                'created_by' => Auth::id(),
                'movement_date' => now(),
                'note' => 'Obyekt boshqaruv panelidan administrator tomonidan qo\'shilgan zaxira.',
            ]);

            AuditLogger::log('add_object_warehouse_stock', $stock, null, [
                'product_id' => $productId,
                'added_qty' => $qty,
            ]);
        });

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Mahsulot zaxirasi muvaffaqiyatli kiritildi.');
    }

    /**
     * Delete Warehouse Stock for Object.
     */
    public function destroyWarehouseStock(Obj $object, WarehouseStock $warehouseStock)
    {
        $warehouseStock->delete();
        AuditLogger::log('delete_object_warehouse_stock', $warehouseStock);
        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Zaxira mahsuloti olib tashlandi.');
    }

    /**
     * Assign Employee to Object.
     */
    public function storeEmployee(Request $request, Obj $object)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required|string|max:255',
            'daily_rate' => 'nullable|integer|min:0',
            'monthly_rate' => 'nullable|integer|min:0',
            'daily_rate_currency' => 'required|string|in:USD,UZS',
            'monthly_rate_currency' => 'required|string|in:USD,UZS',
        ]);

        $employee = ObjectEmployee::create([
            'object_id' => $object->id,
            'user_id' => (int)$request->user_id,
            'position' => $request->position,
            'daily_rate' => $request->filled('daily_rate') ? (int)($request->daily_rate * 100) : 0, // save as cents/tiyin
            'monthly_rate' => $request->filled('monthly_rate') ? (int)($request->monthly_rate * 100) : 0,
            'daily_rate_currency' => $request->daily_rate_currency,
            'monthly_rate_currency' => $request->monthly_rate_currency,
            'hired_at' => now(),
            'permissions' => ['warehouse', 'transactions'], // default permissions
            'is_active' => true,
        ]);

        AuditLogger::log('assign_object_employee', $employee, null, $employee->toArray());

        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Xodim obyektga muvaffaqiyatli biriktirildi.');
    }

    /**
     * Remove Employee from Object.
     */
    public function destroyEmployee(Obj $object, ObjectEmployee $employee)
    {
        $employee->delete();
        AuditLogger::log('remove_object_employee', $employee);
        return redirect()->route('admin.objects.show', $object->id)->with('success', 'Xodim biriktiruvdan bo\'shatildi.');
    }
}
