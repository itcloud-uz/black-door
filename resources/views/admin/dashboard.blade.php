@extends('layouts.app')

@section('title', 'Admin — Bosh sahifa')

@section('breadcrumb')
    <li><span class="current"><i class="bi bi-grid-1x2"></i> Bosh sahifa</span></li>
@endsection

@section('content')

{{-- Stats Cards --}}
<div class="grid-4 mb-xl">
    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-green"><i class="bi bi-currency-dollar"></i></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Jami USD</div>
            <div class="stat-card-value" style="min-width: 0; overflow: hidden; width: 100%;">
                <x-amount-display :amount="$totalUsd ?? 0" currency="USD" scale="true" />
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-copper"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Jami UZS</div>
            <div class="stat-card-value" style="min-width: 0; overflow: hidden; width: 100%;">
                <x-amount-display :amount="$totalUzs ?? 0" currency="UZS" scale="true" />
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-gold"><i class="bi bi-building"></i></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Obyektlar</div>
            <div class="stat-card-value">{{ $objectsCount ?? 0 }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-steel"><i class="bi bi-people"></i></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Foydalanuvchilar</div>
            <div class="stat-card-value">{{ $usersCount ?? 0 }}</div>
        </div>
    </div>
</div>

<div class="grid-2 mb-xl">
    {{-- Cash Accounts --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title"><i class="bi bi-bank"></i> Kassalar va balanslar</h3>
            <a href="{{ route('finance.cash-accounts.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasini ko'rish</a>
        </div>

        @forelse($cashAccounts ?? [] as $account)
            @php
                $usdBalance = ($account->balances ?? collect())->firstWhere('currency.value', 'USD');
                $uzsBalance = ($account->balances ?? collect())->firstWhere('currency.value', 'UZS');
                $usdAmount = $usdBalance ? $usdBalance->amount : 0;
                $uzsAmount = $uzsBalance ? $uzsBalance->amount : 0;
            @endphp
            <div class="d-flex justify-between items-center p-sm" style="border-bottom: 1px solid rgba(184,115,51,0.08); gap: 12px;">
                <div style="flex: 1; min-width: 0;">
                    <strong class="ellipsis" title="{{ $account->name }}">{{ $account->name }}</strong>
                    <span class="text-muted text-sm"> — {{ $account->type->label() }}</span>
                </div>
                <div class="d-flex gap-md" style="flex-shrink: 0; align-items: center;">
                    <x-amount-display :amount="$usdAmount" currency="USD" size="sm" />
                    <span class="text-muted text-xs">|</span>
                    <x-amount-display :amount="$uzsAmount" currency="UZS" size="sm" />
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p class="text-muted">Kassalar mavjud emas</p>
            </div>
        @endforelse
    </div>

    {{-- Objects Overview --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title"><i class="bi bi-building"></i> Obyektlar</h3>
            <a href="{{ route('admin.objects.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasini ko'rish</a>
        </div>

        @forelse($objects ?? [] as $object)
            <div class="d-flex justify-between items-center p-sm" style="border-bottom: 1px solid rgba(184,115,51,0.08);">
                <div>
                    <span>
                        @switch($object->type->value)
                            @case('factory') <i class="bi bi-building-gear"></i> @break
                            @case('construction') <i class="bi bi-cone-striped"></i> @break
                            @case('warehouse') <i class="bi bi-shop"></i> @break
                        @endswitch
                    </span>
                    <strong>{{ $object->name }}</strong>
                </div>
                <div class="d-flex gap-sm items-center">
                    @if($object->activeManager)
                        <span class="text-sm text-muted">{{ $object->activeManager->user->name ?? '—' }}</span>
                    @endif
                    @if($object->subManagers->isNotEmpty())
                        <span class="text-xs text-red font-bold" title="Vaqtinchalik o'rinbosar">(O'r: {{ $object->subManagers->first()->user->name }})</span>
                    @endif
                    <span class="skeuo-badge {{ $object->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                        {{ $object->is_active ? 'Faol' : 'Nofaol' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p class="text-muted">Obyektlar mavjud emas</p>
            </div>
        @endforelse
    </div>
</div>

<div class="grid-2">
    {{-- Recent Transactions --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title"><i class="bi bi-cash-stack"></i> So'nggi tranzaksiyalar</h3>
            <a href="{{ route('finance.transactions.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasini ko'rish</a>
        </div>

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Turi</th>
                        <th>Summa</th>
                        <th>Kassa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions ?? [] as $tx)
                        <tr>
                            <td class="text-sm">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $tx->type->label() }}
                                </span>
                            </td>
                            <td>
                                <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                            </td>
                            <td class="text-sm text-muted">{{ $tx->cashAccount->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Tranzaksiyalar mavjud emas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Audit Log & Exchange Rate --}}
    <div>
        {{-- Exchange Rate --}}
        <div class="skeuo-card mb-lg">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-currency-exchange"></i> Joriy valyuta kursi</h3>
                <a href="{{ route('admin.currency-rates') }}" class="skeuo-btn skeuo-btn-sm">Boshqarish</a>
            </div>
            <div class="cash-register" style="padding: 16px;">
                <div class="register-display register-display-amber" data-exchange-rate="{{ $currentRate ?? 12500 }}">
                    1 USD = {{ number_format($currentRate ?? 12500, 0, '.', ' ') }} UZS
                </div>
            </div>
        </div>

        {{-- Recent Audit --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-journal-text"></i> So'nggi audit yozuvlari</h3>
                <a href="{{ route('admin.audit-log') }}" class="skeuo-btn skeuo-btn-sm">Barchasini ko'rish</a>
            </div>

            @forelse($recentAuditLogs ?? [] as $log)
                <div class="d-flex justify-between items-center p-sm" style="border-bottom: 1px solid rgba(184,115,51,0.06);">
                    <div>
                        <strong class="text-sm">{{ $log->user->name ?? 'Tizim' }}</strong>
                        <span class="text-sm text-muted"> — {{ $log->action }}</span>
                    </div>
                    <span class="text-xs text-muted">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-muted text-sm p-md">Yozuvlar mavjud emas</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
