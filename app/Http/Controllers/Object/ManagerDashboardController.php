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
    protected function getObject()
    {
        $user = Auth::user();
        if ($user->role->value === 'manager') {
            $managedIds = $user->getManagedObjectIds();
            if (empty($managedIds)) {
                return null;
            }
            
            $id = request('object_id') ?: request()->header('X-Object-ID');
            if (!$id && request()->hasSession()) {
                $id = session('active_object_id');
            }
            
            if ($id && in_array((int)$id, $managedIds)) {
                return \App\Models\Obj::find((int)$id);
            }
            
            return \App\Models\Obj::find($managedIds[0]);
        }
        $emp = ObjectEmployee::where('user_id', $user->id)->first();
        return $emp ? $emp->object : null;
    }

    public function index()
    {
        $user = Auth::user();
        $object = $this->getObject();

        if (! $object) {
            return view('manager.no_object');
        }

        $managedObjects = [];
        if ($user->role->value === 'manager') {
            $managedObjects = \App\Models\Obj::whereIn('id', $user->getManagedObjectIds())->get();
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
            'recentMovements',
            'managedObjects'
        ));
    }

    public function switchObject(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'object_id' => 'required|exists:objects,id',
        ]);
        
        $user = Auth::user();
        if ($user->role->value === 'manager') {
            $managedIds = $user->getManagedObjectIds();
            $id = (int)$request->object_id;
            
            if (in_array($id, $managedIds)) {
                session(['active_object_id' => $id]);
                return back()->with('success', 'Obyekt muvaffaqiyatli almashtirildi.');
            }
        }
        
        return back()->withErrors(['object_id' => 'Sizda ushbu obyektni boshqarish huquqi yo\'q.']);
    }
}
