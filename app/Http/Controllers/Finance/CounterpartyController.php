<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Counterparty;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounterpartyController extends Controller
{
    public function index(Request $request)
    {
        $query = Counterparty::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $counterparties = $query->orderBy('name')->get();

        return view('finance.counterparties.index', compact('counterparties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'category' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $counterparty = Counterparty::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'category' => $request->category,
            'note' => $request->note,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('create_counterparty', $counterparty, null, $counterparty->toArray());

        return redirect()->route('finance.counterparties.index')->with('success', 'Kontragent muvaffaqiyatli qo\'shildi.');
    }

    public function show(Counterparty $counterparty)
    {
        $transactions = $counterparty->transactions()->with('cashAccount')->orderBy('created_at', 'desc')->get();
        return view('finance.counterparties.show', compact('counterparty', 'transactions'));
    }
}
