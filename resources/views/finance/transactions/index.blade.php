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
        <h3 class="handwriting-title" style="font-size: 1.4rem; border-bottom: 1px dashed var(--paper-line); padding-bottom: 6px; margin-bottom: 12px;"><i class="bi bi-pencil-square"></i> Yangi Qayd</h3>
        
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

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-paper">Summa</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="skeuo-input skeuo-input-paper" value="{{ old('amount') }}" placeholder="100.00" required>
                    @error('amount') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="max-width: 120px;">
                    <label class="form-label form-label-paper">Valyuta</label>
                    <select name="currency" class="skeuo-select skeuo-select-paper" required>
                        <option value="USD">USD</option>
                        <option value="UZS">UZS</option>
                    </select>
                    @error('currency') <span class="form-error">{{ $message }}</span> @enderror
                </div>
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
                            <td class="text-xs">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
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
</div>

@endsection