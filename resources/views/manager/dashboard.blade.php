@extends('layouts.app')

@section('title', 'Menejer — Bosh sahifa')

@section('breadcrumb')
    <li><span class="current">📊 Obyekt boshqaruvi</span></li>
@endsection

@section('content')

{{-- Object Info --}}
<div class="skeuo-card mb-xl">
    <div class="d-flex items-center gap-md">
        <span style="font-size: 3rem;">
            @switch($object->type->value)
                @case('factory') 🏭 @break
                @case('construction') 🏗️ @break
                @case('warehouse') 🏪 @break
            @endswitch
        </span>
        <div>
            <h1 class="page-title" style="margin: 0;">{{ $object->name }}</h1>
            <p class="text-muted" style="margin: 4px 0 0 0;">📍 {{ $object->address ?? 'Manzil ko'rsatilmagan' }} | Turi: {{ $object->type->label() }}</p>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="grid-4 mb-xl">
    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-steel">👥</div>
        <div class="stat-card-content">
            <div class="stat-card-label">Xodimlar</div>
            <div class="stat-card-value">{{ $employeesCount }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-green">💵</div>
        <div class="stat-card-content">
            <div class="stat-card-label">Oylik to'lov (USD)</div>
            <div class="stat-card-value">
                <x-amount-display :amount="$totalSalaryPaidUsd" currency="USD" />
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-copper">💴</div>
        <div class="stat-card-content">
            <div class="stat-card-label">Oylik to'lov (UZS)</div>
            <div class="stat-card-value">
                <x-amount-display :amount="$totalSalaryPaidUzs" currency="UZS" />
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon stat-card-icon-red">⚠️</div>
        <div class="stat-card-content">
            <div class="stat-card-label">Kamaygan tovarlar</div>
            <div class="stat-card-value">{{ $lowStockCount }}</div>
        </div>
    </div>
</div>

<div class="grid-2 mb-xl">
    {{-- Object Cash Accounts --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">🏦 Obyekt mini-kassalari</h3>
            <a href="{{ route('manager.transactions.index') }}" class="skeuo-btn skeuo-btn-sm">Tranzaksiyalar</a>
        </div>

        @forelse($cashAccounts ?? [] as $account)
            <div class="d-flex justify-between items-center p-sm" style="border-bottom: 1px solid rgba(184,115,51,0.08);">
                <div>
                    <strong>{{ $account->name }}</strong>
                </div>
                <div class="d-flex gap-md">
                    @foreach($account->balances ?? [] as $balance)
                        <x-amount-display :amount="$balance->amount" :currency="$balance->currency->value" size="sm" />
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p class="text-muted">Mini-kassalar mavjud emas</p>
            </div>
        @endforelse
    </div>

    {{-- Stock Levels Overview --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">📦 Ombor zahirasi holati</h3>
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
</div>

<div class="grid-2">
    {{-- Recent Transactions --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">💰 Kassadagi so'nggi amallar</h3>
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

    {{-- Recent movements --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">📦 So'nggi ombor harakatlari</h3>
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
</div>

@endsection