<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\TransactionCategory;
use App\Models\Counterparty;
use App\Enums\TransactionType;
use App\Enums\Currency;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['cashAccount', 'category', 'counterparty']);

        if ($request->filled('cash_account_id')) {
            $query->where('cash_account_id', $request->cash_account_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(30);

        $cashAccounts = CashAccount::where('is_active', true)->orderBy('name')->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $counterparties = Counterparty::orderBy('name')->get();
        $currentRate = \App\Models\CurrencyRate::latest('effective_date')->latest('id')->first();

        return view('finance.transactions.index', compact(
            'transactions',
            'cashAccounts',
            'categories',
            'counterparties',
            'currentRate'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'category_id' => 'nullable|exists:transaction_categories,id',
            'counterparty_id' => 'nullable|exists:counterparties,id',
            'note' => 'nullable|string',
            'destination_cash_account_id' => 'nullable|required_if:type,transfer|exists:cash_accounts,id',
        ]);

        $type = $request->type;
        $amountDecimal = (float)$request->amount;
        $amountCents = (int)round($amountDecimal * 100);
        $currencyVal = $request->currency;
        $cashAccountId = (int)$request->cash_account_id;

        try {
            $createdTransactions = [];

            DB::transaction(function () use ($request, $type, $amountCents, $currencyVal, $cashAccountId, &$createdTransactions) {
                $cashAccount = CashAccount::findOrFail($cashAccountId);
                $latestRate = \App\Models\CurrencyRate::latest('effective_date')->latest('id')->first();
                $rateTiyin = $latestRate ? $latestRate->rate_uzs_per_usd : null;

                if ($type === 'transfer') {
                    $destAccountId = (int)$request->destination_cash_account_id;
                    if ($cashAccountId === $destAccountId) {
                        throw new \Exception('Yuboruvchi va qabul qiluvchi kassa bir xil bo\'lmasligi kerak.');
                    }

                    // Sort cash account IDs to prevent deadlocks
                    $accountIds = [$cashAccountId, $destAccountId];
                    sort($accountIds);

                    $balances = [];
                    foreach ($accountIds as $accId) {
                        CashBalance::firstOrCreate([
                            'cash_account_id' => $accId,
                            'currency' => $currencyVal,
                        ], ['balance' => 0]);

                        $balances[$accId] = CashBalance::where('cash_account_id', $accId)
                            ->where('currency', $currencyVal)
                            ->lockForUpdate()
                            ->first();
                    }

                    $cashBalance = $balances[$cashAccountId];
                    $destBalance = $balances[$destAccountId];

                    if ($cashBalance->balance < $amountCents) {
                        throw new \Exception('Yuboruvchi kassada yetarli mablag\' mavjud emas.');
                    }

                    $destAccount = CashAccount::findOrFail($destAccountId);

                    // Update source balance
                    $sourceNewBalance = $cashBalance->balance - $amountCents;
                    $cashBalance->balance = $sourceNewBalance;
                    $cashBalance->save();

                    // Update destination balance
                    $destNewBalance = $destBalance->balance + $amountCents;
                    $destBalance->balance = $destNewBalance;
                    $destBalance->save();

                    // Create transfer out transaction
                    $txOut = Transaction::create([
                        'cash_account_id' => $cashAccountId,
                        'type' => TransactionType::TransferOut->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $sourceNewBalance,
                        'note' => $request->note ?? "O'tkazma: " . $destAccount->name,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'exchange_rate' => $rateTiyin,
                    ]);

                    // Create transfer in transaction
                    $txIn = Transaction::create([
                        'cash_account_id' => $destAccountId,
                        'type' => TransactionType::TransferIn->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $destNewBalance,
                        'note' => $request->note ?? "O'tkazma: " . $cashAccount->name,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'related_transaction_id' => $txOut->id,
                        'exchange_rate' => $rateTiyin,
                    ]);

                    // Link outbound to inbound
                    $txOut->related_transaction_id = $txIn->id;
                    $txOut->save();

                    $createdTransactions[] = $txOut;
                    $createdTransactions[] = $txIn;

                    AuditLogger::log('create_transfer', $txOut, null, [
                        'source_transaction' => $txOut->toArray(),
                        'destination_transaction' => $txIn->toArray()
                    ]);

                } else {
                    CashBalance::firstOrCreate([
                        'cash_account_id' => $cashAccountId,
                        'currency' => $currencyVal,
                    ], ['balance' => 0]);

                    $cashBalance = CashBalance::where('cash_account_id', $cashAccountId)
                        ->where('currency', $currencyVal)
                        ->lockForUpdate()
                        ->first();

                    if ($type === 'income') {
                        $newBalance = $cashBalance->balance + $amountCents;
                        $cashBalance->balance = $newBalance;
                        $cashBalance->save();

                        $tx = Transaction::create([
                            'cash_account_id' => $cashAccountId,
                            'counterparty_id' => $request->counterparty_id,
                            'category_id' => $request->category_id,
                            'type' => TransactionType::Income->value,
                            'currency' => $currencyVal,
                            'amount' => $amountCents,
                            'balance_after' => $newBalance,
                            'note' => $request->note,
                            'created_by' => Auth::id(),
                            'transaction_date' => now()->toDateString(),
                            'exchange_rate' => $rateTiyin,
                        ]);

                        $createdTransactions[] = $tx;
                        AuditLogger::log('create_transaction', $tx, null, $tx->toArray());

                    } elseif ($type === 'expense') {
                        if ($cashBalance->balance < $amountCents) {
                            throw new \Exception('Kassada yetarli mablag\' mavjud emas.');
                        }

                        $newBalance = $cashBalance->balance - $amountCents;
                        $cashBalance->balance = $newBalance;
                        $cashBalance->save();

                        $tx = Transaction::create([
                            'cash_account_id' => $cashAccountId,
                            'counterparty_id' => $request->counterparty_id,
                            'category_id' => $request->category_id,
                            'type' => TransactionType::Expense->value,
                            'currency' => $currencyVal,
                            'amount' => $amountCents,
                            'balance_after' => $newBalance,
                            'note' => $request->note,
                            'created_by' => Auth::id(),
                            'transaction_date' => now()->toDateString(),
                            'exchange_rate' => $rateTiyin,
                        ]);

                        $createdTransactions[] = $tx;
                        AuditLogger::log('create_transaction', $tx, null, $tx->toArray());
                    }
                }
            });

            // Broadcast created transactions
            foreach ($createdTransactions as $tx) {
                try {
                    broadcast(new \App\Events\TransactionCreated($tx->toArray()))->toOthers();
                } catch (\Throwable $e) {
                    // Ignore broadcast failures
                }
            }

            return redirect()->route('finance.transactions.index')->with('success', 'Tranzaksiya muvaffaqiyatli saqlandi.');

        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function storno(Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                $this->reverseTransaction($transaction);

                // If this is a transfer, also reverse the related transaction
                if ($transaction->relatedTransaction) {
                    $this->reverseTransaction($transaction->relatedTransaction);
                    $transaction->relatedTransaction->delete();
                }

                $transaction->delete();

                AuditLogger::log('storno_transaction', $transaction, $transaction->toArray(), ['storno' => true]);
            });

            return redirect()->route('finance.transactions.index')->with('success', 'Tranzaksiya muvaffaqiyatli bekor qilindi (storno).');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function reverseTransaction(Transaction $tx)
    {
        $cashBalance = CashBalance::where('cash_account_id', $tx->cash_account_id)
            ->where('currency', $tx->currency->value)
            ->first();

        if (! $cashBalance) {
            throw new \Exception('Kassa balansi topilmadi.');
        }

        $direction = $tx->type->balanceDirection();

        if ($direction === 1) {
            // It was an addition, so we subtract
            if ($cashBalance->balance < $tx->amount) {
                throw new \Exception('Storno qilish mumkin emas: kassa qoldig\'i salbiy bo\'lib ketadi.');
            }
            $cashBalance->balance -= $tx->amount;
        } elseif ($direction === -1) {
            // It was a subtraction, so we add back
            $cashBalance->balance += $tx->amount;
        }

        $cashBalance->save();
    }
}
