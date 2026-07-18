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
        ➕ Yangi kassa
    </a>
</div>

<div class="grid-auto">
    @forelse($cashAccounts ?? [] as $account)
        <div class="cash-register">
            {{-- Account Name --}}
            <div class="d-flex justify-between items-center mb-md" style="padding-top: 12px;">
                <h4 style="color: var(--paper-aged); font-weight: 600;">
                    @switch($account->type->value)
                        @case('cash') 💵 @break
                        @case('bank') 🏦 @break
                        @case('card') 💳 @break
                        @case('safe') 🔐 @break
                        @default 💰 @break
                    @endswitch
                    {{ $account->name }}
                </h4>
                <span class="skeuo-badge skeuo-badge-steel">{{ $account->type->label() }}</span>
            </div>

            {{-- Balances --}}
            @foreach($account->balances ?? [] as $balance)
                <div class="d-flex justify-between items-center mb-sm">
                    <x-currency-badge :currency="$balance->currency->value" />
                    <div class="register-display" style="font-size: 1.3rem; padding: 8px 16px; flex: 1; margin-left: 12px;">
                        {{ $balance->currency->format($balance->amount) }}
                    </div>
                </div>
            @endforeach

            @if(($account->balances ?? collect())->isEmpty())
                <div class="register-display" style="font-size: 1rem; padding: 12px;">
                    0.00
                </div>
            @endif

            <div class="bill-slot"></div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--ink-faded);">
            <div style="font-size: 3rem; margin-bottom: 12px;">🏦</div>
            <p>Hali kassalar yaratilmagan</p>
            <a href="{{ route('finance.cash-accounts.create') }}" class="skeuo-btn skeuo-btn-primary mt-md">
                Birinchi kassani yarating
            </a>
        </div>
    @endforelse
</div>

@endsection
