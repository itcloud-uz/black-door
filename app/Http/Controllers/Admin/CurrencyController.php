<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends Controller
{
    public function index()
    {
        $currentRate = CurrencyRate::orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
            
        $rateHistory = CurrencyRate::with('user')
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
            
        return view('admin.currency-rates', compact('currentRate', 'rateHistory'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rate' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $rateVal = (int)$request->rate;
        $rateTiyin = $rateVal * 100;
        $today = now()->toDateString();

        // Use whereDate to reliably match the date portion across SQLite and other engines
        $rate = CurrencyRate::whereDate('effective_date', $today)->first();

        if ($rate) {
            $rate->update([
                'rate_uzs_per_usd' => $rateTiyin,
                'set_by' => Auth::id(),
                'note' => $request->note,
            ]);
        } else {
            $rate = CurrencyRate::create([
                'effective_date' => $today,
                'rate_uzs_per_usd' => $rateTiyin,
                'set_by' => Auth::id(),
                'note' => $request->note,
            ]);
        }

        AuditLogger::log('update_currency_rate', $rate, null, $rate->toArray());

        return redirect()->route('admin.currency-rates')->with('success', 'Valyuta kursi muvaffaqiyatli saqlandi.');
    }

    public function fetchCbuRate()
    {
        $rate = \App\Services\CbuCurrencyService::fetchCbuUsdRate();
        if ($rate !== null) {
            return response()->json([
                'success' => true,
                'rate' => (int) round($rate)
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Markaziy bank kursini olib bo\'lmadi.'
        ], 500);
    }
}
