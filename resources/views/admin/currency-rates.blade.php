@extends('layouts.app')

@section('title', 'Valyuta kurslari')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Valyuta kurslari</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-currency-exchange"></i> Valyuta kurslari</h1>
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
                <span class="text-lg" style="color: var(--paper-aged);">1 USD = <strong class="mono-number">{{ number_format($currentRate->rate ?? 12500, 0, '.', ' ') }}</strong> so'm</span>
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
                <label class="form-label" for="rate">Kurs (1 USD = ? so'm)</label>
                <div style="display: flex; gap: 8px;">
                    <input type="number" id="rate" name="rate" class="skeuo-input"
                           value="{{ old('rate') }}"
                           placeholder="12500"
                           step="1" min="1" required
                           style="font-family: var(--font-mono); font-size: 1.25rem; flex: 1;">
                    <button type="button" id="btn-fetch-cbu" class="skeuo-btn" style="white-space: nowrap;">
                        <i class="bi bi-bank"></i> MB Kursi
                    </button>
                </div>
                @error('rate') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="note">Izoh (ixtiyoriy)</label>
                <input type="text" id="note" name="note" class="skeuo-input"
                       value="{{ old('note') }}" placeholder="Kurs o'zgartirish sababi">
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary">
                <i class="bi bi-save"></i> Saqlash
            </button>
        </form>
    </div>
</div>

{{-- Rate History --}}
<div class="skeuo-card mt-xl">
    <div class="skeuo-card-header">
        <h3 class="skeuo-card-title"><i class="bi bi-clock-history"></i> Kurs tarixi</h3>
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
                                {{ number_format($rate->rate, 0, '.', ' ') }} so'm
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

<script>
function fetchCbuRate(autofillIfEmpty = false) {
    const btn = document.getElementById('btn-fetch-cbu');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Olinmoqda...';

    fetch('{{ route("admin.currency-rates.fetch-cbu") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (autofillIfEmpty) {
                    const rateInput = document.getElementById('rate');
                    if (!rateInput.value) {
                        rateInput.value = data.rate;
                        document.getElementById('note').value = 'Markaziy bank kursi avtomatik yuklandi.';
                    }
                } else {
                    document.getElementById('rate').value = data.rate;
                    document.getElementById('note').value = 'Markaziy bank kursi avtomatik yuklandi.';
                }
                
                // Show/Update live Central Bank rate badge
                let badge = document.getElementById('cbu-live-badge');
                if (!badge) {
                    badge = document.createElement('div');
                    badge.id = 'cbu-live-badge';
                    badge.style.marginTop = '8px';
                    badge.style.fontSize = '0.85rem';
                    badge.style.color = 'var(--accent-green)';
                    document.getElementById('rate').parentNode.parentNode.appendChild(badge);
                }
                badge.innerHTML = `<i class="bi bi-bank"></i> Markaziy bank joriy kursi: <strong class="mono-number" style="color: var(--text-primary); font-size: 0.95rem;">${data.rate} so'm</strong> <span style="font-size: 0.75rem; color: var(--text-secondary);">(Real vaqtda yuklandi)</span>`;
            } else if (!autofillIfEmpty) {
                alert(data.message || 'Xatolik yuz berdi.');
            }
        })
        .catch(err => {
            if (!autofillIfEmpty) {
                alert('Markaziy bank kursini olishda tarmoq xatoligi yuz berdi.');
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

document.getElementById('btn-fetch-cbu').addEventListener('click', function() {
    fetchCbuRate(false);
});

// Auto-fetch on load
document.addEventListener('DOMContentLoaded', function() {
    fetchCbuRate(true);
});
</script>

@endsection
