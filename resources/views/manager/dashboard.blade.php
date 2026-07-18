@extends('layouts.manager')

@section('title', 'Menejer — Bosh sahifa')

@section('breadcrumb')
    <li><span class="current"><i class="bi bi-grid-1x2"></i> Obyekt boshqaruvi</span></li>
@endsection

@section('manager-content')

@php
    $user = auth()->user();
    $isManager = $user->isManager();
    $permissions = $user->isEmployee() ? ($user->objectEmployee?->permissions ?? []) : [];
@endphp

{{-- Object Info --}}
<div class="skeuo-card mb-xl">
    <div class="d-flex items-center gap-md">
        <span style="font-size: 3rem;">
            @switch($object->type->value)
                @case('factory') <i class="bi bi-building-gear"></i> @break
                @case('construction') <i class="bi bi-cone-striped"></i> @break
                @case('warehouse') <i class="bi bi-shop"></i> @break
            @endswitch
        </span>
        <div>
            <h1 class="page-title" style="margin: 0;">{{ $object->name }}</h1>
            <p class="text-muted" style="margin: 4px 0 0 0;">📍 {{ $object->address ?? "Manzil ko'rsatilmagan" }} | Turi: {{ $object->type->label() }}</p>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="grid-4 mb-xl">
    @if($isManager || in_array('employees', $permissions, true))
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-steel"><i class="bi bi-people"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Xodimlar</div>
                <div class="stat-card-value">{{ $employeesCount }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-green"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Oylik to'lov (USD)</div>
                <div class="stat-card-value" style="min-width: 0; overflow: hidden; width: 100%;">
                    <x-amount-display :amount="$totalSalaryPaidUsd" currency="USD" scale="true" />
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-copper"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Oylik to'lov (UZS)</div>
                <div class="stat-card-value" style="min-width: 0; overflow: hidden; width: 100%;">
                    <x-amount-display :amount="$totalSalaryPaidUzs" currency="UZS" scale="true" />
                </div>
            </div>
        </div>
    @endif

    @if($isManager || in_array('warehouse', $permissions, true))
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-card-content">
                <div class="stat-card-label">Kamaygan tovarlar</div>
                <div class="stat-card-value">{{ $lowStockCount }}</div>
            </div>
        </div>
    @endif
</div>

<div class="grid-2 mb-xl">
    {{-- Object Cash Accounts --}}
    @if($isManager || in_array('transactions', $permissions, true))
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-bank"></i> Obyekt mini-kassalari</h3>
                <a href="{{ route('manager.transactions.index') }}" class="skeuo-btn skeuo-btn-sm">Tranzaksiyalar</a>
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
                    </div>
                    <div class="d-flex gap-md" style="flex-shrink: 0; align-items: center;">
                        <x-amount-display :amount="$usdAmount" currency="USD" size="sm" />
                        <span class="text-muted text-xs">|</span>
                        <x-amount-display :amount="$uzsAmount" currency="UZS" size="sm" />
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-muted">Mini-kassalar mavjud emas</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Stock Levels Overview --}}
    @if($isManager || in_array('warehouse', $permissions, true))
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-box-seam"></i> Ombor zahirasi holati</h3>
                <a href="{{ route('manager.warehouse.index') }}" class="skeuo-btn skeuo-btn-sm">Omborga kirish</a>
            </div>
            <div class="p-sm">
                @if($lowStockCount > 0)
                    <div style="background: rgba(220,53,69,0.06); border: 1px dashed var(--accent-red); color: var(--accent-red); padding: 12px; border-radius: 6px; margin-bottom: 12px;" class="text-sm">
                        <strong>Diqqat!</strong> {{ $lowStockCount }} turdagi mahsulot qoldig'i minimal darajadan kamaydi!
                    </div>
                @else
                    <div style="background: rgba(40,167,69,0.06); border: 1px dashed var(--accent-green); color: var(--accent-green); padding: 12px; border-radius: 6px; margin-bottom: 12px;" class="text-sm">
                        ✅ Barcha mahsulotlar yetarli miqdorda mavjud.
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<div class="grid-2">
    {{-- Recent Transactions --}}
    @if($isManager || in_array('transactions', $permissions, true))
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-cash-stack"></i> Kassadagi so'nggi amallar</h3>
                <a href="{{ route('manager.transactions.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasi</a>
            </div>

            <div class="skeuo-table-wrapper">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Sana</th>
                            <th>Kategoriya</th>
                            <th>Turi</th>
                            <th>Summa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions ?? [] as $tx)
                            <tr>
                                <td class="text-sm">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                                <td class="text-sm font-semibold">{{ $tx->category->name ?? '—' }}</td>
                                <td>
                                    <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                        {{ $tx->type->label() }}
                                    </span>
                                </td>
                                <td>
                                    <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Amallar mavjud emas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Recent movements --}}
    @if($isManager || in_array('warehouse', $permissions, true))
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title"><i class="bi bi-box-seam"></i> So'nggi ombor harakatlari</h3>
                <a href="{{ route('manager.warehouse.index') }}" class="skeuo-btn skeuo-btn-sm">Barchasi</a>
            </div>

            <div class="skeuo-table-wrapper">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Sana</th>
                            <th>Mahsulot</th>
                            <th>Harakat</th>
                            <th>Miqdor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements ?? [] as $mvt)
                            <tr>
                                <td class="text-sm">{{ $mvt->created_at->format('d.m.Y H:i') }}</td>
                                <td class="text-sm font-semibold">{{ $mvt->product->name }}</td>
                                <td>
                                    <span class="skeuo-badge {{ $mvt->type->stockDirection() >= 0 ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                        {{ $mvt->type->label() }}
                                    </span>
                                </td>
                                <td class="mono-number">{{ $mvt->quantity }} {{ $mvt->product->unit->value }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Harakatlar mavjud emas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection