<?php

declare(strict_types=1);

namespace App\Http\Controllers\Object;

use App\Http\Controllers\Controller;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\ObjectTransaction;
use App\Models\ObjectTransactionCategory;
use App\Enums\TransactionType;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObjectTransactionController extends Controller
{
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

    public function index(Request $request)
    {
        $object = $this->getObject();
        if (! $object) {
            return redirect()->route('manager.dashboard')->withErrors(['error' => 'Obyekt biriktirilmagan.']);
        }

        $query = ObjectTransaction::with(['cashAccount', 'category'])
            ->where('object_id', $object->id);

        if ($request->filled('cash_account_id')) {
            $query->where('object_cash_account_id', $request->cash_account_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        $cashAccounts = ObjectCashAccount::where('object_id', $object->id)
            ->where('is_active', true)
            ->get();

        $categories = ObjectTransactionCategory::where(function ($q) use ($object) {
                $q->where('object_id', $object->id)->orWhereNull('object_id');
            })
            ->where('is_active', true)
            ->get();

        return view('manager.transactions.index', compact(
            'object',
            'transactions',
            'cashAccounts',
            'categories'
        ));
    }

    public function store(Request $request)
    {
        $object = $this->getObject();
        if (! $object) {
            return back()->withErrors(['error' => 'Obyekt topilmadi.']);
        }

        $request->validate([
            'object_cash_account_id' => 'required|exists:object_cash_accounts,id',
            'category_id' => 'required|exists:object_transaction_categories,id',
            'type' => 'required|string|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string',
            'counterparty_name' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $type = $request->type;
        $currencyVal = $request->currency;
        $amountCents = (int)round((float)$request->amount * 100);
        $cashAccountId = (int)$request->object_cash_account_id;

        try {
            DB::transaction(function () use ($request, $object, $type, $currencyVal, $amountCents, $cashAccountId) {
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

                    $tx = ObjectTransaction::create([
                        'object_id' => $object->id,
                        'object_cash_account_id' => $cashAccountId,
                        'category_id' => $request->category_id,
                        'counterparty_name' => $request->counterparty_name,
                        'type' => TransactionType::Income->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $newBalance,
                        'note' => $request->note,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('create_object_transaction', $tx, null, $tx->toArray());

                } elseif ($type === 'expense') {
                    if ($cashBalance->balance < $amountCents) {
                        throw new \Exception('Kassada yetarli mablag\' mavjud emas.');
                    }

                    $newBalance = $cashBalance->balance - $amountCents;
                    $cashBalance->balance = $newBalance;
                    $cashBalance->save();

                    $tx = ObjectTransaction::create([
                        'object_id' => $object->id,
                        'object_cash_account_id' => $cashAccountId,
                        'category_id' => $request->category_id,
                        'counterparty_name' => $request->counterparty_name,
                        'type' => TransactionType::Expense->value,
                        'currency' => $currencyVal,
                        'amount' => $amountCents,
                        'balance_after' => $newBalance,
                        'note' => $request->note,
                        'created_by' => Auth::id(),
                        'transaction_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('create_object_transaction', $tx, null, $tx->toArray());
                }
            });

            return redirect()->route('manager.transactions.index')->with('success', 'Tranzaksiya muvaffaqiyatli saqlandi.');

        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }
}
