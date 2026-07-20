<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Obj;
use App\Models\User;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\CurrencyRate;
use App\Enums\Currency;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsd = CashBalance::where('currency', Currency::USD->value)->sum('balance');
        $totalUzs = CashBalance::where('currency', Currency::UZS->value)->sum('balance');
        
        $objectsCount = Obj::count();
        $usersCount = User::count();
        
        $cashAccounts = CashAccount::with('balances')->get();
        $objects = Obj::with([
            'activeManager.user',
            'subManagers' => function($query) {
                $query->where('start_date', '<=', now()->toDateString())
                      ->where('end_date', '>=', now()->toDateString())
                      ->with('user');
            }
        ])->get();
        
        $recentTransactions = Transaction::with('cashAccount')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $recentAuditLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $currentRateModel = CurrencyRate::orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $currentRate = $currentRateModel ? $currentRateModel->rate : 12500;
        
        return view('admin.dashboard', compact(
            'totalUsd',
            'totalUzs',
            'objectsCount',
            'usersCount',
            'cashAccounts',
            'objects',
            'recentTransactions',
            'recentAuditLogs',
            'currentRate'
        ));
    }
}
