@extends('layouts.manager')

@section('title', 'Kassa tranzaksiyalari')

@section('breadcrumb')
    <li><a href="{{ route('manager.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Mini-kassa amallari</span></li>
@endsection

@section('manager-content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-cash-stack"></i> Obyekt Mini-kassa amallari</h1>
</div>

<div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
    {{-- Form --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title"><i class="bi bi-plus-lg"></i> Yangi Kassa Qaydi</h3>
        </div>

        <form method="POST" action="{{ route('manager.transactions.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Kassa</label>
                <select name="object_cash_account_id" class="skeuo-select" required>
                    <option value="">— Tanlang —</option>
                    @foreach($cashAccounts ?? [] as $acc)
                        <option value="{{ $acc->id }}" {{ old('object_cash_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('object_cash_account_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Turi</label>
                <select name="type" class="skeuo-select" required>
                    <option value="expense">Chiqim (Xarajat)</option>
                    <option value="income">Kirim</option>
                </select>
                @error('type') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row" style="margin-bottom: 4px;">
                <div class="form-group">
                    <label class="form-label">Summa</label>
                    <input type="number" id="tx-amount" name="amount" step="0.01" min="0.01" class="skeuo-input" placeholder="100.00" required>
                    @error('amount') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="max-width: 100px;">
                    <label class="form-label">Valyuta</label>
                    <select id="tx-currency" name="currency" class="skeuo-select" required>
                        <option value="UZS" {{ old('currency') === 'UZS' ? 'selected' : '' }}>UZS</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                </div>
            </div>

            {{-- Live Currency Converter Helper --}}
            <div id="converter-helper" style="margin-bottom: 12px; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.02); box-shadow: inset 1px 1px 3px rgba(0,0,0,0.06); font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4;">
                <div id="converter-rate-info">Tizim kursi: 1 USD = {{ number_format(($currentRate ? $currentRate->rate : 12500), 0, '.', ' ') }} so'm</div>
                <div id="cbu-rate-info" style="color: var(--accent-green); margin-top: 2px;"><i class="bi bi-bank"></i> MB joriy kursi: yuklanmoqda...</div>
                <div id="converter-result" style="font-weight: 700; color: var(--text-primary); margin-top: 4px; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 4px;">Summa kiriting...</div>
            </div>

            <div class="form-group">
                <label class="form-label">Kategoriya</label>
                <select name="category_id" class="skeuo-select" required>
                    <option value="">— Tanlang —</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->type === 'income' ? '<i class="bi bi-graph-up-arrow"></i>' : '<i class="bi bi-graph-down-arrow"></i>' }} {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kontragent (Kimga/Kimdan)</label>
                <input type="text" name="counterparty_name" class="skeuo-input" placeholder="Masalan: Taksist, Usta Sobir...">
            </div>

            <div class="form-group">
                <label class="form-label">Izoh</label>
                <textarea name="note" class="skeuo-input" placeholder="Batafsil..."></textarea>
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;"><i class="bi bi-save"></i> Saqlash</button>
        </form>
    </div>

    {{-- List --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">Tranzaksiyalar tarixi</h3>
        </div>

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Kassa</th>
                        <th>Turi</th>
                        <th>Kategoriya</th>
                        <th>Kontragent</th>
                        <th>Summa</th>
                        <th>Balans</th>
                        <th>Izoh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions ?? [] as $tx)
                        <tr>
                            <td class="text-sm">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-sm font-semibold">{{ $tx->cashAccount->name ?? '—' }}</td>
                            <td>
                                <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $tx->type->label() }}
                                </span>
                            </td>
                            <td class="text-sm font-semibold">{{ $tx->category->name ?? '—' }}</td>
                            <td class="text-sm">{{ $tx->counterparty_name ?? '—' }}</td>
                            <td>
                                <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                            </td>
                            <td>
                                <x-amount-display :amount="$tx->balance_after" :currency="$tx->currency->value" size="xs" />
                            </td>
                            <td class="text-sm text-muted">{{ $tx->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted p-xl">Tranzaksiyalar topilmadi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($transactions) && method_exists($transactions, 'links'))
            <div class="skeuo-pagination mt-md">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dbRate = {{ $currentRate ? $currentRate->rate : 12500 }};
    let cbuRate = null;

    const amountInput = document.getElementById('tx-amount');
    const currencySelect = document.getElementById('tx-currency');
    const converterResult = document.getElementById('converter-result');
    const cbuInfo = document.getElementById('cbu-rate-info');

    // Fetch Central Bank Rate
    fetch('{{ route("manager.currency-rates.fetch-cbu") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cbuRate = data.rate;
                cbuInfo.innerHTML = `<i class="bi bi-bank"></i> MB joriy kursi: <strong>${new Intl.NumberFormat('uz-UZ').format(cbuRate)} so'm</strong>`;
            } else {
                cbuInfo.style.display = 'none';
            }
            updateConversion();
        })
        .catch(() => {
            cbuInfo.style.display = 'none';
            updateConversion();
        });

    function updateConversion() {
        const val = parseFloat(amountInput.value) || 0;
        const currency = currencySelect.value;

        if (val <= 0) {
            converterResult.innerHTML = 'Summa kiriting...';
            return;
        }

        let dbConversion = '';
        let cbuConversion = '';

        if (currency === 'USD') {
            const uzsDb = val * dbRate;
            dbConversion = `= ${new Intl.NumberFormat('uz-UZ', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(uzsDb)} so'm`;
            
            if (cbuRate) {
                const uzsCbu = val * cbuRate;
                cbuConversion = `<br><span style="font-size: 0.75rem; color: var(--accent-green); font-weight: normal;">MB kursi bo'yicha: ${new Intl.NumberFormat('uz-UZ', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(uzsCbu)} so'm</span>`;
            }
        } else {
            const usdDb = val / dbRate;
            dbConversion = `= $${new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(usdDb)}`;
            
            if (cbuRate && cbuRate > 0) {
                const usdCbu = val / cbuRate;
                cbuConversion = `<br><span style="font-size: 0.75rem; color: var(--accent-green); font-weight: normal;">MB kursi bo'yicha: $${new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(usdCbu)}</span>`;
            }
        }

        converterResult.innerHTML = `${dbConversion}${cbuConversion}`;
    }

    amountInput.addEventListener('input', updateConversion);
    currencySelect.addEventListener('change', updateConversion);
});
</script>
@endpush

@endsection