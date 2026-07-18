<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Enums\Currency;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    public function index()
    {
        $cashAccounts = CashAccount::with('balances')->orderBy('name')->get();
        return view('finance.cash-accounts.index', compact('cashAccounts'));
    }

    public function create()
    {
        return view('finance.cash-accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $account = DB::transaction(function () use ($request) {
            $account = CashAccount::create([
                'name' => $request->name,
                'type' => $request->type,
                'note' => $request->note,
                'is_active' => true,
            ]);

            // Initialize balances for USD and UZS to 0
            CashBalance::create([
                'cash_account_id' => $account->id,
                'currency' => Currency::USD->value,
                'balance' => 0,
            ]);

            CashBalance::create([
                'cash_account_id' => $account->id,
                'currency' => Currency::UZS->value,
                'balance' => 0,
            ]);

            AuditLogger::log('create_cash_account', $account, null, $account->toArray());

            return $account;
        });

        return redirect()->route('finance.cash-accounts.index')->with('success', 'Kassa hisobi muvaffaqiyatli ochildi.');
    }
}
