@extends('layouts.app')

@section('title', 'Valyuta kurslari')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Valyuta kurslari</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">💱 Valyuta kurslari</h1>
</div>

<div class="grid-2">
    {{-- Current Rate Display --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">Joriy kurs</h3>
        </div>

        <div class="cash-register">
            <div class="register-display" data-exchange-rate="{{ $currentRate->rate ?? 12500 }}" style="font-size: 2.5rem; padding: 24px;">
                {{ number_format($currentRate->rate ?? 12500, 0, '.', ' ') }}
            </div>
            <div class="text-center mt-md">
                <span class="text-lg" style="color: var(--paper-aged);">1 USD = <strong class="mono-number">{{ number_format($currentRate->rate ?? 12500, 0, '.', ' ') }}</strong> UZS</span>
            </div>
            @if(isset($currentRate))
                <div class="text-center mt-sm">
                    <span class="text-sm text-muted">O'rnatilgan: {{ $currentRate->created_at->format('d.m.Y H:i') }}</span>
                </div>
            @endif
            <div class="bill-slot" style="margin-top: 20px;"></div>
        </div>
    </div>

    {{-- Add New Rate --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">Yangi kurs qo'shish</h3>
        </div>

        <form method="POST" action="{{ route('admin.currency-rates.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="rate">Kurs (1 USD = ? UZS)</label>
                <input type="number" id="rate" name="rate" class="skeuo-input"
                       value="{{ old('rate') }}"
                       placeholder="12500"
                       step="1" min="1" required
                       style="font-family: var(--font-mono); font-size: 1.25rem;">
                @error('rate') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="note">Izoh (ixtiyoriy)</label>
                <input type="text" id="note" name="note" class="skeuo-input"
                       value="{{ old('note') }}" placeholder="Kurs o'zgartirish sababi">
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary">
                💾 Saqlash
            </button>
        </form>
    </div>
</div>

{{-- Rate History --}}
<div class="skeuo-card mt-xl">
    <div class="skeuo-card-header">
        <h3 class="skeuo-card-title">📜 Kurs tarixi</h3>
    </div>

    <div class="skeuo-table-wrapper">
        <table class="skeuo-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sana</th>
                    <th>Kurs</th>
                    <th>O'rnatgan</th>
                    <th>Izoh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rateHistory ?? [] as $index => $rate)
                    <tr>
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td>{{ $rate->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <span class="mono-number text-gold">
                                {{ number_format($rate->rate, 0, '.', ' ') }} UZS
                            </span>
                        </td>
                        <td class="text-sm">{{ $rate->user->name ?? 'Tizim' }}</td>
                        <td class="text-sm text-muted">{{ $rate->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted p-lg">Kurs tarixi mavjud emas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
