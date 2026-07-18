@extends('layouts.finance')

@section('title', $counterparty->name)

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><a href="{{ route('finance.counterparties.index') }}">Kontragentlar</a></li>
    <li><span class="current">{{ $counterparty->name }}</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg" style="border-bottom: 1px dashed var(--paper-line); padding-bottom: 8px;">
    <h2 class="handwriting-title"><i class="bi bi-person"></i> {{ $counterparty->name }}</h2>
    <a href="{{ route('finance.counterparties.index') }}" class="skeuo-btn skeuo-btn-sm">← Ortga</a>
</div>

<div class="grid-2 mb-xl">
    <div class="skeuo-card-paper">
        <h4 class="handwriting-title" style="font-size: 1.25rem;">Tafsilotlar</h4>
        <p class="text-sm"><strong>Kategoriya:</strong> <span class="skeuo-badge skeuo-badge-steel">{{ $counterparty->category->label() }}</span></p>
        <p class="text-sm"><strong>Telefon:</strong> {{ $counterparty->phone ?? '—' }}</p>
        <p class="text-sm"><strong>Izoh:</strong> {{ $counterparty->note ?? '—' }}</p>
    </div>

    <div class="skeuo-card-paper d-flex flex-column gap-sm">
        <h4 class="handwriting-title" style="font-size: 1.25rem; margin-bottom: 4px;">Balans</h4>
        <div class="d-flex justify-between items-center">
            <span>Balans (USD):</span>
            <strong class="text-lg {{ $counterparty->getBalanceUsd() >= 0 ? 'text-green' : 'text-red' }}">
                {{ $counterparty->balance_usd_formatted }}
            </strong>
        </div>
        <div class="d-flex justify-between items-center">
            <span>Balans (UZS):</span>
            <strong class="text-lg {{ $counterparty->getBalanceUzs() >= 0 ? 'text-green' : 'text-red' }}">
                {{ $counterparty->balance_uzs_formatted }}
            </strong>
        </div>
    </div>
</div>

<div class="skeuo-card-paper">
    <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px;">📜 Tranzaksiyalar tarixi</h3>
    
    <div class="skeuo-table-wrapper">
        <table class="skeuo-table skeuo-table-paper">
            <thead>
                <tr>
                    <th>Sana</th>
                    <th>Kassa</th>
                    <th>Turi</th>
                    <th>Kategoriya</th>
                    <th>Summa</th>
                    <th>Izoh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions ?? [] as $tx)
                    <tr>
                        <td class="text-xs">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-sm font-semibold">{{ $tx->cashAccount->name ?? '—' }}</td>
                        <td>
                            <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                {{ $tx->type->label() }}
                            </span>
                        </td>
                        <td class="text-sm">{{ $tx->category->name ?? '—' }}</td>
                        <td>
                            <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                        </td>
                        <td class="text-sm text-muted">{{ $tx->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted p-lg">Hali tranzaksiyalar amalga oshirilmagan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection