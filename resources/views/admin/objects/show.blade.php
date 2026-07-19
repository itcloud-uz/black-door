@extends('layouts.app')

@section('title', $object->name . ' - Tafsilotlar')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><a href="{{ route('admin.objects.index') }}">Obyektlar</a></li>
    <li><span class="current">{{ $object->name }}</span></li>
@endsection

@section('content')

<div class="page-header">
    <div class="d-flex items-center gap-sm">
        <span style="font-size: 2rem; color: var(--accent-green);">
            @switch($object->type->value)
                @case('factory') <i class="bi bi-building-gear"></i> @break
                @case('construction') <i class="bi bi-cone-striped"></i> @break
                @case('warehouse') <i class="bi bi-shop"></i> @break
            @endswitch
        </span>
        <div>
            <h1 class="page-title" style="margin: 0;">{{ $object->name }}</h1>
            <span class="text-sm text-muted">{{ $object->type->label() }} &bull; {{ $object->address ?? 'Manzil kiritilmagan' }}</span>
        </div>
    </div>
    <div class="d-flex gap-sm">
        <a href="{{ route('admin.objects.edit', $object->id) }}" class="skeuo-btn skeuo-btn-neutral">
            <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        <form action="{{ route('admin.objects.destroy', $object->id) }}" method="POST" onsubmit="return confirm('Haqiqatdan ham ushbu obyektni o\'chirmoqchimisiz? Ushbu amal ortga qaytmaydi!');">
            @csrf
            @method('DELETE')
            <button type="submit" class="skeuo-btn skeuo-btn-red">
                <i class="bi bi-trash"></i> O'chirish
            </button>
        </form>
    </div>
</div>

{{-- Top Analytics Cards --}}
<div class="grid-4 mb-xl">
    {{-- UZS Balance --}}
    <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
        <div class="text-xs text-muted mb-xs uppercase">UZS Balans</div>
        <div class="amount-display amount-positive" style="font-size: 1.5rem; font-weight: 800; white-space: nowrap;">
            {{ number_format($totalUZS / 100, 2, '.', ' ') }} so'm
        </div>
    </div>

    {{-- USD Balance --}}
    <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
        <div class="text-xs text-muted mb-xs uppercase">USD Balans</div>
        <div class="amount-display amount-positive" style="font-size: 1.5rem; font-weight: 800; white-space: nowrap;">
            ${{ number_format($totalUSD / 100, 2, '.', ' ') }}
        </div>
    </div>

    {{-- Manager Card --}}
    <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
        <div class="text-xs text-muted mb-xs uppercase">Boshqaruvchi</div>
        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
            <i class="bi bi-person"></i> {{ $object->activeManager->user->name ?? 'Menejer biriktirilmagan' }}
        </div>
    </div>

    {{-- Status Card --}}
    <div class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div class="text-xs text-muted mb-xs uppercase">Holat</div>
            <span class="skeuo-badge {{ $object->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}" style="font-size: 0.9rem;">
                {{ $object->is_active ? 'Faol' : 'Nofaol' }}
            </span>
        </div>
        <div class="text-xs text-muted" style="text-align: right;">
            Qo'shilgan: <br><strong>{{ $object->created_at->format('d.m.Y') }}</strong>
        </div>
    </div>
</div>

{{-- Main Details Tab View --}}
<div x-data="{ activeTab: 'cash' }">
    
    {{-- Segmented Tab Bar (Soft UI Neumorphic) --}}
    <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-lg); padding: 8px; border-radius: var(--radius-lg); background: var(--surface); box-shadow: var(--shadow-pressed-sm); flex-wrap: wrap;">
        <button type="button" class="skeuo-btn" style="flex: 1; min-width: 160px; border: none; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; transition: all 0.25s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.95rem;"
                :style="activeTab === 'cash' ? 'background: var(--surface); color: var(--text-primary); box-shadow: var(--shadow-raised-sm); border: 1px solid rgba(255,255,255,0.5);' : 'background: transparent; color: var(--text-muted); box-shadow: none; border: 1px solid transparent;'"
                @click="activeTab = 'cash'">
            <i class="bi bi-bank" :style="activeTab === 'cash' ? 'color: var(--success);' : ''"></i> Kassalar ({{ $object->cashAccounts->count() }})
        </button>
        
        <button type="button" class="skeuo-btn" style="flex: 1; min-width: 160px; border: none; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; transition: all 0.25s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.95rem;"
                :style="activeTab === 'stock' ? 'background: var(--surface); color: var(--text-primary); box-shadow: var(--shadow-raised-sm); border: 1px solid rgba(255,255,255,0.5);' : 'background: transparent; color: var(--text-muted); box-shadow: none; border: 1px solid transparent;'"
                @click="activeTab = 'stock'">
            <i class="bi bi-box-seam" :style="activeTab === 'stock' ? 'color: #e67e22;' : ''"></i> Ombor / Mahsulotlar ({{ $object->warehouseStocks->count() }})
        </button>
        
        <button type="button" class="skeuo-btn" style="flex: 1; min-width: 160px; border: none; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; transition: all 0.25s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.95rem;"
                :style="activeTab === 'staff' ? 'background: var(--surface); color: var(--text-primary); box-shadow: var(--shadow-raised-sm); border: 1px solid rgba(255,255,255,0.5);' : 'background: transparent; color: var(--text-muted); box-shadow: none; border: 1px solid transparent;'"
                @click="activeTab = 'staff'">
            <i class="bi bi-people" :style="activeTab === 'staff' ? 'color: #3498db;' : ''"></i> Obyekt xodimlari ({{ $object->employees->count() }})
        </button>
        
        <button type="button" class="skeuo-btn" style="flex: 1; min-width: 160px; border: none; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; transition: all 0.25s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.95rem;"
                :style="activeTab === 'txs' ? 'background: var(--surface); color: var(--text-primary); box-shadow: var(--shadow-raised-sm); border: 1px solid rgba(255,255,255,0.5);' : 'background: transparent; color: var(--text-muted); box-shadow: none; border: 1px solid transparent;'"
                @click="activeTab = 'txs'">
            <i class="bi bi-cash-stack" :style="activeTab === 'txs' ? 'color: #9b59b6;' : ''"></i> Tranzaksiyalar ({{ $object->transactions->count() }})
        </button>
    </div>

    {{-- Tab Content --}}
    <div style="width: 100%;">
        
        {{-- Cash Accounts Tab --}}
        <div x-show="activeTab === 'cash'" class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-bank text-green"></i> Obyekt kassa hisoblari</h3>
                <span class="text-xs text-muted">Barcha kassalar va ularning joriy qoldiqlari</span>
            </div>

            <div class="grid-2 mb-xl" style="align-items: start;">
                {{-- List of Cash Accounts --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @forelse($object->cashAccounts as $account)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 12px; background: var(--surface); box-shadow: var(--shadow-neutral-sm);">
                            <div>
                                <strong style="font-size: 0.95rem; color: var(--text-primary);">{{ $account->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                                    Turi: {{ $account->type->label() }}
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 16px;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span class="amount-positive" style="font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                                        {{ number_format($account->getBalance(\App\Enums\Currency::UZS) / 100, 2, '.', ' ') }} so'm
                                    </span>
                                    <span class="amount-positive" style="font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                                        ${{ number_format($account->getBalance(\App\Enums\Currency::USD) / 100, 2, '.', ' ') }}
                                    </span>
                                </div>
                                <form action="{{ route('admin.objects.cash-accounts.destroy', [$object->id, $account->id]) }}" method="POST" onsubmit="return confirm('Kassani o\'chirishni tasdiqlaysizmi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: var(--accent-red); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Hali kassalar qo'shilmagan.</p>
                    @endforelse
                </div>

                {{-- Add Cash Account Form --}}
                <div class="skeuo-card" style="box-shadow: var(--shadow-pressed-sm); background: var(--surface); padding: 16px;">
                    <h4 style="margin: 0 0 16px 0; font-size: 1rem;"><i class="bi bi-plus-circle"></i> Yangi kassa qo'shish</h4>
                    <form method="POST" action="{{ route('admin.objects.cash-accounts.store', $object->id) }}">
                        @csrf
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="cash_name" style="font-size: 0.8rem;">Kassa nomi</label>
                            <input type="text" id="cash_name" name="name" class="skeuo-input" placeholder="Masalan: Asosiy seyf" required style="font-size: 0.85rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label class="form-label" for="cash_type" style="font-size: 0.8rem;">Turi</label>
                            <select id="cash_type" name="type" class="skeuo-select" required style="font-size: 0.85rem;">
                                <option value="cash">Naqd pul</option>
                                <option value="bank">Bank hisob</option>
                                <option value="card">Karta</option>
                                <option value="safe">Seyf</option>
                                <option value="other">Boshqa</option>
                            </select>
                        </div>
                        <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="font-size: 0.85rem; padding: 8px;">
                            Qo'shish
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Warehouse Stock Tab --}}
        <div x-show="activeTab === 'stock'" class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-box-seam text-copper"></i> Ombor zaxiralari</h3>
                <span class="text-xs text-muted">Mahsulot qoldiqlari va me'yoriy chegaralar</span>
            </div>

            <div class="grid-2 mb-xl" style="align-items: start;">
                {{-- List of Stocks --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @forelse($object->warehouseStocks as $stock)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 12px; background: var(--surface); box-shadow: var(--shadow-neutral-sm);">
                            <div>
                                <strong style="font-size: 0.95rem; color: var(--text-primary);">{{ $stock->product->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                                    Me'yoriy chegara: {{ $stock->product->min_stock_level }} {{ $stock->product->unit->label() }}
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 16px;">
                                <div>
                                    <span style="font-size: 1.1rem; font-weight: bold; color: {{ $stock->isLow() ? 'var(--accent-red)' : 'var(--text-primary)' }}">
                                        {{ $stock->quantity }} {{ $stock->product->unit->abbreviation() }}
                                    </span>
                                    @if($stock->isLow())
                                        <div style="font-size: 0.7rem; color: var(--accent-red); margin-top: 2px;">Kam qoldi!</div>
                                    @endif
                                </div>
                                <form action="{{ route('admin.objects.warehouse-stocks.destroy', [$object->id, $stock->id]) }}" method="POST" onsubmit="return confirm('Zaxirani olib tashlashni tasdiqlaysizmi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: var(--accent-red); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Omborda hali mahsulotlar mavjud emas.</p>
                    @endforelse
                </div>

                {{-- Add/Adjust Stock Form --}}
                <div class="skeuo-card" style="box-shadow: var(--shadow-pressed-sm); background: var(--surface); padding: 16px;">
                    <h4 style="margin: 0 0 16px 0; font-size: 1rem;"><i class="bi bi-plus-circle"></i> Zaxira qo'shish / kiritish</h4>
                    <form method="POST" action="{{ route('admin.objects.warehouse-stocks.store', $object->id) }}">
                        @csrf
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="product_id" style="font-size: 0.8rem;">Mahsulot</label>
                            <select id="product_id" name="product_id" class="skeuo-select" required style="font-size: 0.85rem;">
                                <option value="">— Tanlang —</option>
                                @foreach($availableProducts as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label class="form-label" for="quantity" style="font-size: 0.8rem;">Miqdori (Qo'shiladigan)</label>
                            <input type="number" id="quantity" name="quantity" min="1" class="skeuo-input" placeholder="Miqdori" required style="font-size: 0.85rem;">
                        </div>
                        <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="font-size: 0.85rem; padding: 8px;">
                            Kirim qilish
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Staff Tab --}}
        <div x-show="activeTab === 'staff'" class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-people text-gold"></i> Biriktirilgan xodimlar</h3>
                <span class="text-xs text-muted">Ishchilar ro'yxati va ularning stavkalari</span>
            </div>

            <div class="grid-2 mb-xl" style="align-items: start;">
                {{-- List of Employees --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @forelse($object->employees as $emp)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 12px; background: var(--surface); box-shadow: var(--shadow-neutral-sm);">
                            <div>
                                <strong style="font-size: 0.95rem; color: var(--text-primary);">{{ $emp->user->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">
                                    Lavozimi: {{ $emp->position }}
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 16px;">
                                <div style="display: flex; flex-direction: column; gap: 2px; font-size: 0.8rem; color: var(--text-secondary);">
                                    @if($emp->daily_rate > 0)
                                        <span>Kunlik: <strong>{{ number_format($emp->daily_rate / 100, 2) }} {{ $emp->daily_rate_currency->value }}</strong></span>
                                    @endif
                                    @if($emp->monthly_rate > 0)
                                        <span>Oylik: <strong>{{ number_format($emp->monthly_rate / 100, 2) }} {{ $emp->monthly_rate_currency->value }}</strong></span>
                                    @endif
                                </div>
                                <form action="{{ route('admin.objects.employees.destroy', [$object->id, $emp->id]) }}" method="POST" onsubmit="return confirm('Xodimni biriktiruvdan bo\'shatishni tasdiqlaysizmi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: var(--accent-red); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Obyektga hali xodimlar biriktirilmagan.</p>
                    @endforelse
                </div>

                {{-- Assign Employee Form --}}
                <div class="skeuo-card" style="box-shadow: var(--shadow-pressed-sm); background: var(--surface); padding: 16px;">
                    <h4 style="margin: 0 0 16px 0; font-size: 1rem;"><i class="bi bi-person-plus"></i> Xodim biriktirish</h4>
                    <form method="POST" action="{{ route('admin.objects.employees.store', $object->id) }}">
                        @csrf
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="user_id" style="font-size: 0.8rem;">Xodim</label>
                            <select id="user_id" name="user_id" class="skeuo-select" required style="font-size: 0.85rem;">
                                <option value="">— Tanlang —</option>
                                @foreach($availableEmployees as $usr)
                                    <option value="{{ $usr->id }}">{{ $usr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="position" style="font-size: 0.8rem;">Lavozimi</label>
                            <input type="text" id="position" name="position" class="skeuo-input" placeholder="Masalan: Usta" required style="font-size: 0.85rem;">
                        </div>

                        <div class="form-row" style="gap: 8px; margin-bottom: 12px;">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label" for="daily_rate" style="font-size: 0.8rem;">Kunlik stavka</label>
                                <input type="number" id="daily_rate" name="daily_rate" min="0" class="skeuo-input" placeholder="Stavka" style="font-size: 0.85rem;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" for="daily_rate_currency" style="font-size: 0.8rem;">Valyuta</label>
                                <select id="daily_rate_currency" name="daily_rate_currency" class="skeuo-select" style="font-size: 0.85rem;">
                                    <option value="UZS">UZS</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row" style="gap: 8px; margin-bottom: 16px;">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label" for="monthly_rate" style="font-size: 0.8rem;">Oylik stavka</label>
                                <input type="number" id="monthly_rate" name="monthly_rate" min="0" class="skeuo-input" placeholder="Stavka" style="font-size: 0.85rem;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" for="monthly_rate_currency" style="font-size: 0.8rem;">Valyuta</label>
                                <select id="monthly_rate_currency" name="monthly_rate_currency" class="skeuo-select" style="font-size: 0.85rem;">
                                    <option value="UZS">UZS</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="skeuo-btn skeuo-btn-primary w-full" style="font-size: 0.85rem; padding: 8px;">
                            Biriktirish
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Transactions Tab --}}
        <div x-show="activeTab === 'txs'" class="skeuo-card" style="box-shadow: var(--shadow-neutral-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-cash-stack text-green"></i> Obyekt tranzaksiyalari (Tarix)</h3>
                <span class="text-xs text-muted">So'nggi moliya amallari ro'yxati</span>
            </div>

            <div class="table-responsive">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Sana</th>
                            <th>Kassa</th>
                            <th>Kategoriya</th>
                            <th>Turi</th>
                            <th>Summa</th>
                            <th>Izoh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($object->transactions->sortByDesc('transaction_date') as $tx)
                            <tr>
                                <td style="white-space: nowrap;">{{ $tx->transaction_date->format('d.m.Y') }}</td>
                                <td>{{ $tx->cashAccount->name ?? '—' }}</td>
                                <td>{{ $tx->category->name ?? '—' }}</td>
                                <td>
                                    <span class="skeuo-badge {{ $tx->type->value === 'income' ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                        {{ $tx->type->label() }}
                                    </span>
                                </td>
                                <td class="{{ $tx->type->value === 'income' ? 'amount-positive' : 'amount-negative' }}" style="font-weight: bold; white-space: nowrap;">
                                    {{ $tx->type->value === 'income' ? '+' : '-' }}{{ $tx->currency->value === 'USD' ? '$' : '' }}{{ number_format($tx->amount / 100, 2, '.', ' ') }}{{ $tx->currency->value === 'UZS' ? ' so\'m' : '' }}
                                </td>
                                <td>{{ $tx->note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Ushbu obyekt bo'yicha tranzaksiyalar mavjud emas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@endsection
