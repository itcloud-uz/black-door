@extends('layouts.finance')

@section('title', 'Kontragentlar')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current">Kontragentlar</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg">
    <h2 class="handwriting-title">👥 Kontragentlar Reyestri</h2>
</div>

<div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
    {{-- Form --}}
    <div class="skeuo-card-paper">
        <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px;">👤 Yangi Kontragent</h3>
        
        <form method="POST" action="{{ route('finance.counterparties.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label form-label-paper">Nomi</label>
                <input type="text" name="name" class="skeuo-input skeuo-input-paper" value="{{ old('name') }}" placeholder="Kontragent korxonasi yoki shaxs nomi" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Telefon</label>
                <input type="text" name="phone" class="skeuo-input skeuo-input-paper" value="{{ old('phone') }}" placeholder="+998 90 123 45 67">
                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Kategoriya</label>
                <select name="category" class="skeuo-select skeuo-select-paper" required>
                    <option value="client">Mijoz</option>
                    <option value="supplier">Yetkazib beruvchi</option>
                    <option value="partner">Hamkor</option>
                    <option value="other">Boshqa</option>
                </select>
                @error('category') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Izoh</label>
                <textarea name="note" class="skeuo-input skeuo-input-paper" placeholder="Kontragent haqida qo'shimcha ma'lumot...">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;">💾 Saqlash</button>
        </form>
    </div>

    {{-- List --}}
    <div class="skeuo-card-paper">
        <div class="skeuo-table-wrapper">
            <table class="skeuo-table skeuo-table-paper">
                <thead>
                    <tr>
                        <th>Nomi</th>
                        <th>Kategoriya</th>
                        <th>Telefon</th>
                        <th>Balans (USD)</th>
                        <th>Balans (UZS)</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($counterparties ?? [] as $cp)
                        <tr>
                            <td>
                                <strong>
                                    <a href="{{ route('finance.counterparties.show', $cp->id) }}" style="color: inherit; text-decoration: underline;">
                                        {{ $cp->name }}
                                    </a>
                                </strong>
                            </td>
                            <td><span class="skeuo-badge skeuo-badge-steel">{{ $cp->category->label() }}</span></td>
                            <td class="text-sm">{{ $cp->phone ?? '—' }}</td>
                            <td>
                                @php $balUsd = $cp->getBalanceUsd(); @endphp
                                <span class="{{ $balUsd > 0 ? 'text-green' : ($balUsd < 0 ? 'text-red' : 'text-muted') }}">
                                    {{ $cp->balance_usd_formatted }}
                                </span>
                            </td>
                            <td>
                                @php $balUzs = $cp->getBalanceUzs(); @endphp
                                <span class="{{ $balUzs > 0 ? 'text-green' : ($balUzs < 0 ? 'text-red' : 'text-muted') }}">
                                    {{ $cp->balance_uzs_formatted }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.counterparties.show', $cp->id) }}" class="skeuo-btn skeuo-btn-sm">📜 Jurnal</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted p-xl">Kontragentlar topilmadi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection