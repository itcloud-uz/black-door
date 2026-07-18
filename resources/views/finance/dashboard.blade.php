@extends('layouts.finance')

@section('title', 'Moliya  Bosh sahifa')

@section('finance-content')

<div class="d-flex justify-between items-center mb-xl">
    <h2 class="handwriting-title">💼 Moliyaviy Holat</h2>
    <span class="text-sm text-muted">Kurs: 1 USD = {{ number_format($currentRate, 0, '.', ' ') }} UZS</span>
</div>

{{-- Total Balances --}}
<div class="grid-2 mb-xl">
    <div class="cash-register" style="padding: 20px;">
        <div class="text-sm text-muted mb-xs">💵 Jami USD Balans</div>
        <div class="register-display register-display-amber" style="font-size: 2.2rem; padding: 12px;">
            <x-amount-display :amount="$totalUsd" currency="USD" />
        </div>
    </div>
    <div class="cash-register" style="padding: 20px;">
        <div class="text-sm text-muted mb-xs">💴 Jami UZS Balans</div>
        <div class="register-display register-display-amber" style="font-size: 2.2rem; padding: 12px;">
            <x-amount-display :amount="$totalUzs" currency="UZS" />
        </div>
    </div>
</div>

<div class="grid-2">
    {{-- Kassalar --}}
    <div class="skeuo-card-paper">
        <div class="d-flex justify-between items-center mb-md" style="border-bottom: 1px dashed var(--paper-line); padding-bottom: 8px;">
            <h3 class="handwriting-title" style="font-size: 1.5rem; margin: 0;">🏦 Kassalar</h3>
            <a href="{{ route('finance.cash-accounts.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasi</a>
        </div>
        
        @forelse($cashAccounts ?? [] as $account)
            <div class="d-flex justify-between items-center p-sm" style="border-bottom: 1px dashed var(--paper-line);">
                <div>
                    <strong>{{ $account->name }}</strong>
                    <span class="text-muted text-xs">  {{ $account->type->label() }}</span>
                </div>
                <div class="d-flex gap-md">
                    @foreach($account->balances ?? [] as $balance)
                        <x-amount-display :amount="$balance->amount" :currency="$balance->currency->value" size="sm" />
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted text-center py-md">Kassalar topilmadi</p>
        @endforelse
    </div>

    {{-- So'nggi amallar --}}
    <div class="skeuo-card-paper">
        <div class="d-flex justify-between items-center mb-md" style="border-bottom: 1px dashed var(--paper-line); padding-bottom: 8px;">
            <h3 class="handwriting-title" style="font-size: 1.5rem; margin: 0;">📝 So'nggi tranzaksiyalar</h3>
            <a href="{{ route('finance.transactions.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasi</a>
        </div>

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table skeuo-table-paper">
                <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Turi</th>
                        <th>Kassa</th>
                        <th>Summa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions ?? [] as $tx)
                        <tr>
                            <td class="text-xs">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $tx->type->label() }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $tx->cashAccount->name ?? '' }}</td>
                            <td>
                                <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Tranzaksiyalar yo'q</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection