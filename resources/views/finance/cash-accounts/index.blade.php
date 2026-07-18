@extends('layouts.finance')

@section('title', 'Kassalar')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current">Kassalar</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg" style="flex-wrap: wrap; gap: 12px;">
    <h2 class="handwriting-title">Kassalar</h2>
    <a href="{{ route('finance.cash-accounts.create') }}" class="skeuo-btn skeuo-btn-primary" style="color: white;">
        <i class="bi bi-plus-lg"></i> Yangi kassa
    </a>
</div>

<div class="grid-auto">
    @forelse($cashAccounts ?? [] as $account)
        <div class="cash-register">
            {{-- Account Name --}}
            <div class="d-flex justify-between items-center mb-md" style="padding-top: 12px;">
                <h4 style="color: var(--paper-aged); font-weight: 600;">
                    @switch($account->type->value)
                        @case('cash') <i class="bi bi-currency-dollar"></i> @break
                        @case('bank') <i class="bi bi-bank"></i> @break
                        @case('card') <i class="bi bi-credit-card"></i> @break
                        @case('safe') <i class="bi bi-shield-lock"></i> @break
                        @default <i class="bi bi-cash-stack"></i> @break
                    @endswitch
                    {{ $account->name }}
                </h4>
                <span class="skeuo-badge skeuo-badge-steel">{{ $account->type->label() }}</span>
            </div>

            {{-- Balances (USD and UZS always visible) --}}
            @php
                $usdBalance = ($account->balances ?? collect())->firstWhere('currency.value', 'USD');
                $uzsBalance = ($account->balances ?? collect())->firstWhere('currency.value', 'UZS');
                $usdAmount = $usdBalance ? $usdBalance->amount : 0;
                $uzsAmount = $uzsBalance ? $uzsBalance->amount : 0;
            @endphp
            <div class="d-flex justify-between items-center mb-sm align-center" style="gap: 12px;">
                <x-currency-badge currency="USD" style="flex-shrink: 0;" />
                <div class="register-display" style="font-size: 1.3rem; padding: 8px 16px; flex: 1; display: flex; justify-content: flex-end; align-items: center; min-height: 42px;">
                    <x-amount-display :amount="$usdAmount" currency="USD" size="md" />
                </div>
            </div>
            <div class="d-flex justify-between items-center mb-sm align-center" style="gap: 12px;">
                <x-currency-badge currency="UZS" style="flex-shrink: 0;" />
                <div class="register-display" style="font-size: 1.3rem; padding: 8px 16px; flex: 1; display: flex; justify-content: flex-end; align-items: center; min-height: 42px;">
                    <x-amount-display :amount="$uzsAmount" currency="UZS" size="md" />
                </div>
            </div>

            <div class="bill-slot"></div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--ink-faded);">
            <div style="font-size: 3rem; margin-bottom: 12px;"><i class="bi bi-bank"></i></div>
            <p>Hali kassalar yaratilmagan</p>
            <a href="{{ route('finance.cash-accounts.create') }}" class="skeuo-btn skeuo-btn-primary mt-md">
                Birinchi kassani yarating
            </a>
        </div>
    @endforelse
</div>

@endsection
