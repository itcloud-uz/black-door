<?php

declare(strict_types=1);

namespace App\Http\Controllers\Object;

use App\Http\Controllers\Controller;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\ObjectTransaction;
use App\Models\WarehouseStock;
use App\Models\WarehouseMovement;
use App\Models\SalaryPayment;
use Illuminate\Support\Facades\Auth;

class ManagerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $object = null;

        if ($user->role->value === 'manager') {
            $mgr = ObjectManager::where('user_id', $user->id)->first();
            if ($mgr) {
                $object = $mgr->object;
            }
        } elseif ($user->role->value === 'employee') {
            $emp = ObjectEmployee::where('user_id', $user->id)->first();
            if ($emp) {
                $object = $emp->object;
            }
        }

        if (! $object) {
            return view('manager.no_object');
        }

        // Stats
        $employeesCount = ObjectEmployee::where('object_id', $object->id)
            ->where('is_active', true)
            ->count();

        $totalSalaryPaidUsd = SalaryPayment::where('object_id', $object->id)
            ->where('currency', 'USD')
            ->sum('amount');
            
        $totalSalaryPaidUzs = SalaryPayment::where('object_id', $object->id)
            ->where('currency', 'UZS')
            ->sum('amount');

        // Cash Accounts
        $cashAccounts = ObjectCashAccount::with('balances')
            ->where('object_id', $object->id)
            ->where('is_active', true)
            ->get();

        // Stock count where low stock
        $lowStockCount = WarehouseStock::where('object_id', $object->id)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->get()
            ->filter(fn ($stock) => $stock->isLow())
            ->count();

        // Recent mini-cash transactions
        $recentTransactions = ObjectTransaction::with('category')
            ->where('object_id', $object->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent warehouse movements
        $recentMovements = WarehouseMovement::with('product')
            ->where('object_id', $object->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('manager.dashboard', compact(
            'object',
            'employeesCount',
            'totalSalaryPaidUsd',
            'totalSalaryPaidUzs',
            'cashAccounts',
            'lowStockCount',
            'recentTransactions',
            'recentMovements'
        ));
    }
}
