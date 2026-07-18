<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Transaction;
use App\Models\CurrencyRate;
use App\Enums\Currency;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsd = CashBalance::where('currency', Currency::USD->value)->sum('balance');
        $totalUzs = CashBalance::where('currency', Currency::UZS->value)->sum('balance');

        $cashAccounts = CashAccount::with('balances')->where('is_active', true)->get();
        $recentTransactions = Transaction::with(['cashAccount', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $currentRateModel = CurrencyRate::orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $currentRate = $currentRateModel ? $currentRateModel->rate : 12500;

        return view('finance.dashboard', compact(
            'totalUsd',
            'totalUzs',
            'cashAccounts',
            'recentTransactions',
            'currentRate'
        ));
    }
}
