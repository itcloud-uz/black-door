<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Counterparty;
use App\Models\CounterpartyTag;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Enums\TransactionType;
use App\Enums\Currency;
use App\Services\AuditLogger;
use App\Services\AnalyticsClient;
use App\Events\TransactionCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    /**
     * Cash Accounts List
     */
    public function listCashAccounts()
    {
        $accounts = CashAccount::where('is_active', true)->orderBy('name')->get()->map(function($acc) {
            return [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type->value,
                'note' => $acc->note,
                'usd_balance' => $acc->usdBalance() / 100,
                'uzs_balance' => $acc->uzsBalance() / 100,
            ];
        });

        return response()->json($accounts);
    }

    /**
     * Counterparties List
     */
    public function listCounterparties(Request $request)
    {
        $query = Counterparty::with('tags');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('id', $request->tag_id);
            });
        }

        $counterparties = $query->orderBy('name')->get()->map(function($cp) {
            return [
                'id' => $cp->id,
                'name' => $cp->name,
                'phone' => $cp->phone,
                'note' => $cp->note,
                'category' => $cp->category->value,
                'usd_balance' => $cp->getBalanceUsd() / 100,
                'uzs_balance' => $cp->getBalanceUzs() / 100,
                'tags' => $cp->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
            ];
        });

        return response()->json($counterparties);
    }

    public function storeCounterparty(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'note' => 'nullable|string',
            'category' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:counterparty_tags,id',
        ]);

        $cp = DB::transaction(function () use ($request) {
            $counterparty = Counterparty::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'note' => $request->note,
                'category' => $request->category,
                'created_by' => Auth::id(),
            ]);

            if ($request->filled('tags')) {
                $counterparty->tags()->sync($request->tags);
            }

            return $counterparty;
        });

        AuditLogger::log('create_counterparty', $cp, null, $cp->toArray());

        return response()->json([
            'message' => 'Kontragent muvaffaqiyatli yaratildi.',
            'counterparty' => $cp
        ], 201);
    }

    public function showCounterparty(Counterparty $counterparty)
    {
        $transactions = $counterparty->transactions()
            ->with('cashAccount')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function($tx) {
                return [
                    'id' => $tx->id,
                    'type' => $tx->type->value,
                    'currency' => $tx->currency->value,
                    'amount' => $tx->amount / 100,
                    'date' => $tx->transaction_date,
                    'note' => $tx->note,
                    'cash_account' => $tx->cashAccount->name ?? null,
                ];
            });

        return response()->json([
            'id' => $counterparty->id,
            'name' => $counterparty->name,
            'phone' => $counterparty->phone,
            'note' => $counterparty->note,
            'category' => $counterparty->category->value,
            'usd_balance' => $counterparty->getBalanceUsd() / 100,
            'uzs_balance' => $counterparty->getBalanceUzs() / 100,
            'transactions' => $transactions
        ]);
    }

    /**
     * Categories Tree
     */
    public function listCategories()
    {
        $categories = TransactionCategory::with('children')->whereNull('parent_id')->get();
        return response()->json($categories);
    }

    /**
     * Transactions List
     */
    public function listTransactions(Request $request)
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

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(30);

        return response()->json($transactions);
    }

    /**
     * Store Transaction
     */
    public function storeTransaction(Request $request)
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
            'attachment' => 'nullable|image|max:5120', // Chek/fayl
            // Currency Exchange specific params
            'to_currency' => 'nullable|required_if:type,exchange|string',
            'exchange_rate' => 'nullable|required_if:type,exchange|numeric|min:0.001',
        ]);

        $type = $request->type;
        $amountVal = (float)$request->amount;
        $amountCents = (int)round($amountVal * 100);
        $currencyVal = $request->currency;
        $cashAccountId = (int)$request->cash_account_id;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        try {
            $txResult = DB::transaction(function () use ($request, $type, $amountCents, $currencyVal, $cashAccountId, $attachmentPath) {
                $cashAccount = CashAccount::findOrFail($cashAccountId);
                $cashBalance = CashBalance::where('cash_account_id', $cashAccountId)
                    ->where('currency', $currencyVal)
                    ->firstOrCreate([
                        'cash_account_id' => $cashAccountId,
                        'currency' => $currencyVal,
                    ], ['balance' => 0]);

                $latestRate = \App\Models\CurrencyRate::latest('effective_date')->latest('id')->first();
                $rateTiyin = $latestRate ? $latestRate->rate_uzs_per_usd : null;

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
                        'attachment_path' => $attachmentPath,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'exchange_rate' => $rateTiyin,
                    ]);

                    AuditLogger::log('create_transaction', $tx, null, $tx->toArray());
                    broadcast(new TransactionCreated($tx->toArray()))->toOthers();
                    return $tx;

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
                        'attachment_path' => $attachmentPath,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'exchange_rate' => $rateTiyin,
                    ]);

                    AuditLogger::log('create_transaction', $tx, null, $tx->toArray());
                    broadcast(new TransactionCreated($tx->toArray()))->toOthers();
                    return $tx;

                } elseif ($type === 'transfer') {
                    $destAccountId = (int)$request->destination_cash_account_id;
                    if ($cashAccountId === $destAccountId) {
                        throw new \Exception('Yuboruvchi va qabul qiluvchi kassa bir xil bo\'lmasligi kerak.');
                    }

                    if ($cashBalance->balance < $amountCents) {
                        throw new \Exception('Yuboruvchi kassada yetarli mablag\' mavjud emas.');
                    }

                    $destAccount = CashAccount::findOrFail($destAccountId);
                    $destBalance = CashBalance::where('cash_account_id', $destAccountId)
                        ->where('currency', $currencyVal)
                        ->firstOrCreate([
                            'cash_account_id' => $destAccountId,
                            'currency' => $currencyVal,
                        ], ['balance' => 0]);

                    // Update source balance
                    $sourceNewBalance = $cashBalance->balance - $amountCents;
                    $cashBalance->balance = $sourceNewBalance;
                    $cashBalance->save();

                    // Update destination balance
                    $destNewBalance = $destBalance->balance + $amountCents;
                    $destBalance->balance = $destNewBalance;
                    $destBalance->save();

                    // Outflow tx
                    $txOut = Transaction::create([
                        'cash_account_id' => $cashAccountId,
                        'type' => TransactionType::TransferOut->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $sourceNewBalance,
                        'note' => $request->note ?? "O'tkazma: " . $destAccount->name,
                        'attachment_path' => $attachmentPath,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'exchange_rate' => $rateTiyin,
                    ]);

                    // Inflow tx
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

                    $txOut->related_transaction_id = $txIn->id;
                    $txOut->save();

                    AuditLogger::log('create_transfer', $txOut, null, [
                        'source' => $txOut->toArray(),
                        'destination' => $txIn->toArray()
                    ]);

                    broadcast(new TransactionCreated($txOut->toArray()))->toOthers();
                    broadcast(new TransactionCreated($txIn->toArray()))->toOthers();

                    return $txOut;

                } elseif ($type === 'exchange') {
                    $toCurrency = $request->to_currency;
                    $rate = (float)$request->exchange_rate;

                    if ($currencyVal === $toCurrency) {
                        throw new \Exception('Ayirboshlash valyutalari bir xil bo\'lmasligi kerak.');
                    }

                    if ($cashBalance->balance < $amountCents) {
                        throw new \Exception('Hisobda yetarli mablag\' mavjud emas.');
                    }

                    // Calculate destination amount
                    if ($currencyVal === Currency::USD->value && $toCurrency === Currency::UZS->value) {
                        $toAmountCents = (int)round(($amountCents / 100) * $rate * 100);
                    } elseif ($currencyVal === Currency::UZS->value && $toCurrency === Currency::USD->value) {
                        $toAmountCents = (int)round(($amountCents / 100) / $rate * 100);
                    } else {
                        throw new \Exception('Noma\'lum valyuta ayirboshlash juftligi.');
                    }

                    $destBalance = CashBalance::where('cash_account_id', $cashAccountId)
                        ->where('currency', $toCurrency)
                        ->firstOrCreate([
                            'cash_account_id' => $cashAccountId,
                            'currency' => $toCurrency,
                        ], ['balance' => 0]);

                    // Deduct from_currency
                    $fromNewBalance = $cashBalance->balance - $amountCents;
                    $cashBalance->balance = $fromNewBalance;
                    $cashBalance->save();

                    // Add to_currency
                    $toNewBalance = $destBalance->balance + $toAmountCents;
                    $destBalance->balance = $toNewBalance;
                    $destBalance->save();

                    // Transaction 1: Deducting Currency A
                    $txOut = Transaction::create([
                        'cash_account_id' => $cashAccountId,
                        'type' => TransactionType::Exchange->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $fromNewBalance,
                        'note' => $request->note ?? "Valyuta ayirboshlash (chiqim): {$amountVal} {$currencyVal} -> {$toCurrency} (kurs: {$rate})",
                        'attachment_path' => $attachmentPath,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'exchange_rate' => $rateTiyin,
                    ]);

                    // Transaction 2: Adding Currency B
                    $txIn = Transaction::create([
                        'cash_account_id' => $cashAccountId,
                        'type' => TransactionType::Exchange->value,
                        'currency' => $toCurrency,
                        'amount' => $toAmountCents,
                        'balance_after' => $toNewBalance,
                        'note' => $request->note ?? "Valyuta ayirboshlash (kirim): {$toCurrency} (kurs: {$rate})",
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                        'related_transaction_id' => $txOut->id,
                        'exchange_rate' => $rateTiyin,
                    ]);

                    $txOut->related_transaction_id = $txIn->id;
                    $txOut->save();

                    AuditLogger::log('exchange_currency', $txOut, null, [
                        'from' => $txOut->toArray(),
                        'to' => $txIn->toArray()
                    ]);

                    broadcast(new TransactionCreated($txOut->toArray()))->toOthers();
                    broadcast(new TransactionCreated($txIn->toArray()))->toOthers();

                    return $txOut;
                }
            });

            return response()->json([
                'message' => 'Tranzaksiya muvaffaqiyatli saqlandi.',
                'transaction' => $txResult
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Storno Transaction
     */
    public function stornoTransaction(Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                $this->reverseTransaction($transaction);

                if ($transaction->relatedTransaction) {
                    $this->reverseTransaction($transaction->relatedTransaction);
                    $transaction->relatedTransaction->delete();
                }

                $transaction->delete();

                AuditLogger::log('storno_transaction', $transaction, $transaction->toArray(), ['storno' => true]);
            });

            return response()->json([
                'message' => 'Tranzaksiya muvaffaqiyatli bekor qilindi (storno).'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function reverseTransaction(Transaction $tx)
    {
        $cashBalance = CashBalance::where('cash_account_id', $tx->cash_account_id)
            ->where('currency', $tx->currency->value)
            ->first();

        if (!$cashBalance) {
            throw new \Exception('Kassa balansi topilmadi.');
        }

        $direction = $tx->type->balanceDirection();

        if ($direction === 1) {
            if ($cashBalance->balance < $tx->amount) {
                throw new \Exception('Storno qilish mumkin emas: kassa qoldig\'i salbiy bo\'lib ketadi.');
            }
            $cashBalance->balance -= $tx->amount;
        } elseif ($direction === -1) {
            $cashBalance->balance += $tx->amount;
        } elseif ($tx->type->value === TransactionType::Exchange->value) {
            // Exchange needs specific reverse
            // If related transaction is there, reverse it too.
            // For outflow exchange: it deducted, so add back.
            // For inflow exchange: it added, so subtract.
            if ($tx->related_transaction_id === null) {
                // This is the inflow leg (has parent/related_id on children, wait relatedTransaction points to either)
                // Let's check which one this is.
                // In our code, txOut has related_transaction_id points to txIn. txIn has related_transaction_id points to txOut.
                // So both point to each other.
            }
            
            // To simplify, if the current transaction has a related transaction, we will reverse both by their net amounts.
            // If it was a subtraction (e.g. USD outflow), add it back. If it was addition (UZS inflow), subtract it.
            // Let's inspect the notes or amount logic:
            // Since we know the transaction has the amount and currency, we can check if it is outflow or inflow.
            // In exchange, we can check if this transaction has relatedTransaction.
            // Wait, we can determine the direction: if the balance_after + amount equals previous balance, or if it is inflow/outflow.
            // Since we don't have a direct direction in Exchange enum balanceDirection (returns 0), we can check:
            // If the transaction note contains "(chiqim)" or is the parent (having relatedTransaction and relatedTransaction->id > transaction->id)
            // Or more simply: since we reverse BOTH in storno, we can detect if it's the one that had balance deducted (which has relatedTransaction created after it, or note has 'chiqim')
            // Let's do a simple check:
            // If this transaction's amount is deducted, or let's look at its balance change.
            // Actually, we can check: does the cash balance have more than amount if it's an inflow? Yes, we can just subtract if it's inflow, add if it's outflow.
            // Let's see: is this an addition or subtraction?
            // In storno, if it is the parent ($tx->related_transaction_id != null), we know $tx is the outflow (it deducted balance), so we add back.
            // And the child is the inflow (it added balance), so we subtract.
            if ($tx->related_transaction_id !== null && $tx->id < $tx->related_transaction_id) {
                // This is txOut (outflow, deducted from cash) -> add back
                $cashBalance->balance += $tx->amount;
            } else {
                // This is txIn (inflow, added to cash) -> subtract
                if ($cashBalance->balance < $tx->amount) {
                    throw new \Exception('Storno qilish mumkin emas: kassa qoldig\'i salbiy bo\'lib ketadi.');
                }
                $cashBalance->balance -= $tx->amount;
            }
        }

        $cashBalance->save();
    }

    /**
     * Reports API
     */
    public function getReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $type = $request->input('type', 'income_expense');
        $cashAccountId = $request->filled('cash_account_id') ? (int)$request->cash_account_id : null;
        $categoryId = $request->filled('category_id') ? (int)$request->category_id : null;

        $client = new AnalyticsClient();

        try {
            $reportData = [];
            if ($type === 'income_expense') {
                $reportData = $client->getIncomeExpenseReport($startDate, $endDate, $cashAccountId, $categoryId);
            } elseif ($type === 'cash_balances') {
                $reportData = $client->getCashBalancesReport($startDate, $endDate);
            } elseif ($type === 'debt_registry') {
                $reportData = $client->getDebtRegistryReport($endDate);
            } elseif ($type === 'category_breakdown') {
                $reportData = $client->getCategoryBreakdownReport($startDate, $endDate, $request->input('category_type', 'expense'));
            }

            return response()->json($reportData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hisobot olishda xatolik: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getCurrentRate()
    {
        $rate = \App\Models\CurrencyRate::latest('effective_date')->latest('id')->first();
        return response()->json([
            'rate' => $rate ? ($rate->rate_uzs_per_usd / 100) : 12500,
            'updated_at' => $rate ? $rate->created_at->toIso8601String() : null
        ]);
    }
}
