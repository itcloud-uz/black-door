@extends('layouts.app')

@section('title', 'Ombor boshqaruvi')

@section('breadcrumb')
    <li><a href="{{ route('manager.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Ombor</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">📦 Obyekt Omborxona boshqaruvi</h1>
</div>

<div class="grid-2 mb-xl">
    {{-- Product Stock Levels --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">Zaxira Qoldiqlari</h3>
        </div>

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Mahsulot</th>
                        <th>Birlik</th>
                        <th>Joriy zaxira</th>
                        <th>Minimal miqdor</th>
                        <th>Holat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks ?? [] as $stock)
                        <tr>
                            <td><strong>{{ $stock->product->name }}</strong></td>
                            <td>{{ $stock->product->unit->value }}</td>
                            <td class="mono-number">{{ $stock->quantity }}</td>
                            <td class="mono-number text-muted">{{ $stock->product->min_stock_level }}</td>
                            <td>
                                @if($stock->isLow())
                                    <span class="skeuo-badge skeuo-badge-red">⚠️ Kamaygan</span>
                                @else
                                    <span class="skeuo-badge skeuo-badge-green">✅ Yetarli</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Ombor bo'sh</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Movement Log Form --}}
    <div class="skeuo-card" x-data="{ mvtType: 'incoming' }">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">✍️ Ombor harakatini yozish</h3>
        </div>

        <form method="POST" action="{{ route('manager.warehouse.movement') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Mahsulot</label>
                <select name="product_id" class="skeuo-select" required>
                    <option value="">— Tanlang —</option>
                    @foreach($products ?? [] as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit->value }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Turi</label>
                <select name="type" class="skeuo-select" required x-model="mvtType">
                    <option value="incoming">Kirim (Keltirildi)</option>
                    <option value="outgoing">Chiqim (Ishlatildi)</option>
                    <option value="transfer">Boshqa obyektga o'tkazish</option>
                </select>
            </div>

            {{-- Transfer Destination --}}
            <div class="form-group" x-show="mvtType === 'transfer'" x-transition>
                <label class="form-label">Qabul qiluvchi obyekt</label>
                <select name="to_object_id" class="skeuo-select">
                    <option value="">— Tanlang —</option>
                    @foreach($otherObjects ?? [] as $obj)
                        <option value="{{ $obj->id }}">{{ $obj->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Miqdor</label>
                <input type="number" name="quantity" min="1" class="skeuo-input" required placeholder="10">
                @error('quantity') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Qabul qiluvchi shaxs ismi</label>
                <input type="text" name="recipient_name" class="skeuo-input" placeholder="Masalan: Sobirjon">
            </div>

            <div class="form-group">
                <label class="form-label">Izoh</label>
                <textarea name="note" class="skeuo-input" placeholder="Izoh yozing..."></textarea>
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;">💾 Saqlash</button>
        </form>
    </div>
</div>

{{-- Inventory Check Form --}}
<div class="skeuo-card mb-xl">
    <div class="skeuo-card-header">
        <h3 class="skeuo-card-title">🔍 Inventarizatsiya (Zaxirani solishtirish va to'g'rilash)</h3>
    </div>

    <form method="POST" action="{{ route('manager.warehouse.check') }}">
        @csrf

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Mahsulot</th>
                        <th>Kutilayotgan miqdor (Tizimda)</th>
                        <th>Haqiqiy miqdor (Omborda)</th>
                        <th>Farq sababi / Izoh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks ?? [] as $index => $stock)
                        <tr>
                            <td>
                                <strong>{{ $stock->product->name }}</strong>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $stock->product_id }}">
                            </td>
                            <td class="mono-number">{{ $stock->quantity }}</td>
                            <td>
                                <input type="number" name="items[{{ $index }}][actual_qty]" class="skeuo-input" style="max-width: 120px;" value="{{ $stock->quantity }}" min="0" required>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][note]" class="skeuo-input" placeholder="Tuzatish izohi">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Ombor bo'sh, inventarizatsiya qilib bo'lmaydi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->count() > 0)
            <div class="p-md" style="border-top: 1px solid rgba(184,115,51,0.1); display: flex; justify-content: space-between; align-items: center;">
                <div class="form-group" style="margin: 0; flex: 1; max-width: 500px; margin-right: 20px;">
                    <input type="text" name="note" class="skeuo-input" placeholder="Umumiy inventarizatsiya izohi (ixtiyoriy)">
                </div>
                <button type="submit" class="skeuo-btn skeuo-btn-success" onsubmit="return confirm('Haqiqiy qoldiqlarni tasdiqlaysizmi? Tizim avtomatik ravishda farqlarni tuzatadi.');">✅ Inventarizatsiyani yakunlash</button>
            </div>
        @endif
    </form>
</div>

{{-- Movements History --}}
<div class="skeuo-card">
    <div class="skeuo-card-header">
        <h3 class="skeuo-card-title">📜 Ombor harakatlari tarixi</h3>
    </div>

    <div class="skeuo-table-wrapper">
        <table class="skeuo-table">
            <thead>
                <tr>
                    <th>Sana</th>
                    <th>Mahsulot</th>
                    <th>Harakat turi</th>
                    <th>Miqdor</th>
                    <th>Obyekt</th>
                    <th>Mas'ul</th>
                    <th>Izoh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements ?? [] as $mvt)
                    <tr>
                        <td class="text-sm">{{ $mvt->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-sm font-semibold">{{ $mvt->product->name }}</td>
                        <td>
                            <span class="skeuo-badge {{ $mvt->type->stockDirection() >= 0 ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                {{ $mvt->type->label() }}
                            </span>
                        </td>
                        <td class="mono-number">{{ $mvt->quantity }} {{ $mvt->product->unit->value }}</td>
                        <td class="text-sm">
                            @if($mvt->type->value === 'transfer')
                                ➡️ {{ $mvt->toObject->name ?? '—' }}
                            @elseif($mvt->fromObject)
                                ⬅️ {{ $mvt->fromObject->name ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-sm">{{ $mvt->creator->name ?? '—' }}</td>
                        <td class="text-sm text-muted">{{ $mvt->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted p-lg">Harakatlar tarixi bo'sh.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($movements) && method_exists($movements, 'links'))
        <div class="skeuo-pagination mt-md">
            {{ $movements->links() }}
        </div>
    @endif
</div>

@endsection