@extends('layouts.finance')

@section('title', 'Tranzaksiyalar')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current">Tranzaksiyalar</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg" style="flex-wrap: wrap; gap: 12px;">
    <h2 class="handwriting-title"><i class="bi bi-cash-stack"></i> Tranzaksiyalar Jurnali</h2>
</div>

{{-- Add Transaction Form & List --}}
<div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
    {{-- Form --}}
    <div class="skeuo-card-paper" x-data="{ txType: 'income' }">
        <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span><i class="bi bi-pencil-square"></i> Yangi Qayd</span>
            @if(isset($currentRate))
                <span class="text-gold mono-number" style="font-size: 0.9rem; font-weight: bold;">
                    1 USD = {{ number_format($currentRate->rate, 0, '.', ' ') }} so'm
                </span>
            @endif
        </h3>
        
        <form method="POST" action="{{ route('finance.transactions.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label form-label-paper">Kassa</label>
                <select name="cash_account_id" class="skeuo-select skeuo-select-paper" required>
                    <option value="">— Tanlang —</option>
                    @foreach($cashAccounts ?? [] as $acc)
                        <option value="{{ $acc->id }}" {{ old('cash_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('cash_account_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Turi</label>
                <select name="type" class="skeuo-select skeuo-select-paper" required x-model="txType">
                    <option value="income">Kirim</option>
                    <option value="expense">Chiqim</option>
                    <option value="transfer">O'tkazma</option>
                </select>
                @error('type') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            {{-- Transfer Destination --}}
            <div class="form-group" x-show="txType === 'transfer'" x-transition>
                <label class="form-label form-label-paper">Qabul qiluvchi kassa</label>
                <select name="destination_cash_account_id" class="skeuo-select skeuo-select-paper">
                    <option value="">— Tanlang —</option>
                    @foreach($cashAccounts ?? [] as $acc)
                        <option value="{{ $acc->id }}" {{ old('destination_cash_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('destination_cash_account_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row" style="margin-bottom: 4px;">
                <div class="form-group">
                    <label class="form-label form-label-paper">Summa</label>
                    <input type="number" id="tx-amount" name="amount" step="0.01" min="0.01" class="skeuo-input skeuo-input-paper" value="{{ old('amount') }}" placeholder="100.00" required>
                    @error('amount') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="max-width: 120px;">
                    <label class="form-label form-label-paper">Valyuta</label>
                    <select id="tx-currency" name="currency" class="skeuo-select skeuo-select-paper" required>
                        <option value="USD">USD</option>
                        <option value="UZS">UZS</option>
                    </select>
                    @error('currency') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Live Currency Converter Helper --}}
            <div id="converter-helper" style="margin-bottom: 12px; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.02); box-shadow: inset 1px 1px 3px rgba(0,0,0,0.06); font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4;">
                <div id="converter-rate-info">Tizim kursi: 1 USD = {{ number_format(($currentRate ? $currentRate->rate : 12500), 0, '.', ' ') }} so'm</div>
                <div id="cbu-rate-info" style="color: var(--accent-green); margin-top: 2px;"><i class="bi bi-bank"></i> MB joriy kursi: yuklanmoqda...</div>
                <div id="converter-result" style="font-weight: 700; color: var(--text-primary); margin-top: 4px; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 4px;">Summa kiriting...</div>
            </div>

            <div class="form-group" x-show="txType !== 'transfer'">
                <label class="form-label form-label-paper">Kategoriya</label>
                <select name="category_id" class="skeuo-select skeuo-select-paper">
                    <option value="">— Tanlang (ixtiyoriy) —</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->type === 'income' ? '<i class="bi bi-graph-up-arrow"></i>' : '<i class="bi bi-graph-down-arrow"></i>' }} {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" x-show="txType !== 'transfer'">
                <label class="form-label form-label-paper">Kontragent</label>
                <select name="counterparty_id" class="skeuo-select skeuo-select-paper">
                    <option value="">— Tanlang (ixtiyoriy) —</option>
                    @foreach($counterparties ?? [] as $cp)
                        <option value="{{ $cp->id }}" {{ old('counterparty_id') == $cp->id ? 'selected' : '' }}>
                            <i class="bi bi-person"></i> {{ $cp->name }} ({{ $cp->category->label() }})
                        </option>
                    @endforeach
                </select>
                @error('counterparty_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label-paper">Izoh</label>
                <textarea name="note" class="skeuo-input skeuo-input-paper" placeholder="Tranzaksiya tafsilotlari...">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;"><i class="bi bi-save"></i> Qayd etish</button>
        </form>
    </div>

    {{-- Transactions List --}}
    <div class="skeuo-card-paper">
        {{-- Table --}}
        <div class="skeuo-table-wrapper">
            <table class="skeuo-table skeuo-table-paper">
                <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Kassa</th>
                        <th>Turi</th>
                        <th>Kategoriya</th>
                        <th>Kontragent</th>
                        <th>Summa</th>
                        <th>Balans</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions ?? [] as $tx)
                        <tr>
                            <td class="text-xs">
                                {{ $tx->created_at->format('d.m.Y H:i') }}
                                @if($tx->exchange_rate)
                                    <div class="text-muted" style="font-size: 10px; margin-top: 4px; white-space: nowrap;">
                                        Kurs: {{ number_format($tx->exchange_rate / 100, 0, '.', ' ') }} so'm
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm font-semibold">{{ $tx->cashAccount->name ?? '—' }}</td>
                            <td>
                                <span class="skeuo-badge {{ $tx->type->isCredit() ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $tx->type->label() }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $tx->category->name ?? '—' }}</td>
                            <td class="text-sm">{{ $tx->counterparty->name ?? '—' }}</td>
                            <td>
                                <x-amount-display :amount="$tx->amount" :currency="$tx->currency->value" size="sm" />
                            </td>
                            <td>
                                <x-amount-display :amount="$tx->balance_after" :currency="$tx->currency->value" size="xs" />
                            </td>
                            <td>
                                <form method="POST" action="{{ route('finance.transactions.storno', $tx->id) }}" onsubmit="return confirm('Tranzaksiyani storno qilmoqchimisiz? Bu amalni ortga qaytarib bo'lmaydi!');">
                                    @csrf
                                    <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-danger" title="Bekor qilish (Storno)"><i class="bi bi-arrow-counterclockwise"></i> Storno</button>
                                </form>
                            </td>
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
    fetch('{{ route("finance.currency-rates.fetch-cbu") }}')
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