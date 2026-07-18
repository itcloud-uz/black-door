@extends('layouts.finance')

@section('title', 'Hisobotlar')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><span class="current">Hisobotlar</span></li>
@endsection

@section('finance-content')

<div class="d-flex justify-between items-center mb-lg">
    <h2 class="handwriting-title">📊 Moliyaviy Hisobotlar</h2>
</div>

{{-- Filters --}}
<div class="skeuo-card-paper mb-xl">
    <form method="GET" action="{{ route('finance.reports.index') }}">
        <div class="filter-row" style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
            <div class="filter-group" style="flex: 1; min-width: 180px;">
                <label class="form-label form-label-paper">Hisobot turi</label>
                <select name="type" class="skeuo-select skeuo-select-paper" onchange="this.form.submit()">
                    <option value="income_expense" {{ $type === 'income_expense' ? 'selected' : '' }}>Kirim-Chiqim hisoboti</option>
                    <option value="cash_balances" {{ $type === 'cash_balances' ? 'selected' : '' }}>Kassa qoldiqlari balansi</option>
                    <option value="debt_registry" {{ $type === 'debt_registry' ? 'selected' : '' }}>Qarzlar reyestri</option>
                    <option value="category_breakdown" {{ $type === 'category_breakdown' ? 'selected' : '' }}>Kategoriya bo'yicha tahlil</option>
                </select>
            </div>

            <div class="filter-group" style="flex: 1; min-width: 140px;">
                <label class="form-label form-label-paper">Boshlanish sanasi</label>
                <input type="date" name="start_date" class="skeuo-input skeuo-input-paper" value="{{ $startDate }}">
            </div>

            <div class="filter-group" style="flex: 1; min-width: 140px;">
                <label class="form-label form-label-paper">Tugash sanasi</label>
                <input type="date" name="end_date" class="skeuo-input skeuo-input-paper" value="{{ $endDate }}">
            </div>

            @if($type === 'income_expense')
                <div class="filter-group" style="flex: 1; min-width: 140px;">
                    <label class="form-label form-label-paper">Kassa</label>
                    <select name="cash_account_id" class="skeuo-select skeuo-select-paper">
                        <option value="">Barchasi</option>
                        @foreach($cashAccounts ?? [] as $acc)
                            <option value="{{ $acc->id }}" {{ $cashAccountId == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="filter-group" style="flex: 0 0 auto; align-self: flex-end;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary">🔍 Generatsiya</button>
            </div>
        </div>
    </form>
</div>

{{-- Report Output --}}
@if(isset($reportData['error']) && $reportData['error'])
    <div style="background: rgba(220,53,69,0.1); border: 1px dashed var(--accent-red); padding: var(--space-md); border-radius: 8px; margin-bottom: var(--space-xl); color: var(--accent-red);">
        <strong>⚠️ Xizmat xatosi:</strong> {{ $reportData['message'] }}
    </div>
@elseif(empty($reportData))
    <div class="text-center text-muted py-xl">
        <p>Generatsiya tugmasini bosing yoki filtrni to'g'rilang.</p>
    </div>
@else
    <div class="skeuo-card-paper">
        <div class="d-flex justify-between items-center mb-md" style="border-bottom: 1px dashed var(--paper-line); padding-bottom: 8px;">
            <h3 class="handwriting-title" style="font-size: 1.4rem; margin: 0;">📋 Hisobot natijalari</h3>
            
            {{-- Export Buttons --}}
            <div class="d-flex gap-sm">
                <form method="POST" action="{{ route('finance.reports.export', 'excel') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="cash_account_id" value="{{ $cashAccountId }}">
                    <button type="submit" class="skeuo-btn skeuo-btn-sm" style="background: #217346; color: white;">📥 Excel Yuklab olish</button>
                </form>
                <form method="POST" action="{{ route('finance.reports.export', 'pdf') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="cash_account_id" value="{{ $cashAccountId }}">
                    <button type="submit" class="skeuo-btn skeuo-btn-sm" style="background: #d32f2f; color: white;">📥 PDF Yuklab olish</button>
                </form>
            </div>
        </div>

        {{-- Dynamic Report Tables depending on Type --}}
        @if($type === 'income_expense')
            <div class="d-flex gap-xl mb-md text-sm" style="font-family: var(--font-handwriting); font-size: 1.25rem;">
                <div>Kirim USD: <strong class="text-green">${{ number_format($reportData['total_income_usd']/100, 2) }}</strong></div>
                <div>Chiqim USD: <strong class="text-red">${{ number_format($reportData['total_expense_usd']/100, 2) }}</strong></div>
                <div>Kirim UZS: <strong class="text-green">{{ number_format($reportData['total_income_uzs']/100, 2) }} UZS</strong></div>
                <div>Chiqim UZS: <strong class="text-red">{{ number_format($reportData['total_expense_uzs']/100, 2) }} UZS</strong></div>
            </div>

            <div class="skeuo-table-wrapper">
                <table class="skeuo-table skeuo-table-paper">
                    <thead>
                        <tr>
                            <th>Sana</th>
                            <th>Kategoriya</th>
                            <th>Turi</th>
                            <th>USD</th>
                            <th>UZS</th>
                            <th>Izoh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['transactions'] ?? $reportData['items'] ?? [] as $item)
                            <tr>
                                <td class="text-xs">{{ $item['date'] ?? '—' }}</td>
                                <td class="text-sm font-semibold">{{ $item['category'] ?? '—' }}</td>
                                <td>
                                    <span class="skeuo-badge {{ ($item['type'] ?? '') === 'income' ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                        {{ ($item['type'] ?? '') === 'income' ? 'Kirim' : 'Chiqim' }}
                                    </span>
                                </td>
                                <td>
                                    @if(isset($item['amount_usd']) && $item['amount_usd'] > 0)
                                        ${{ number_format($item['amount_usd']/100, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if(isset($item['amount_uzs']) && $item['amount_uzs'] > 0)
                                        {{ number_format($item['amount_uzs']/100, 2) }} UZS
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm text-muted">{{ $item['note'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Ushbu davrda tranzaksiyalar topilmadi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($type === 'cash_balances')
            <div class="skeuo-table-wrapper">
                <table class="skeuo-table skeuo-table-paper">
                    <thead>
                        <tr>
                            <th>Kassa nomi</th>
                            <th>Valyuta</th>
                            <th>Sana</th>
                            <th>Balans</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['accounts'] ?? [] as $acc)
                            @foreach($acc['daily_balances'] ?? [] as $bal)
                                <tr>
                                    <td><strong>{{ $acc['account_name'] }}</strong></td>
                                    <td>{{ $acc['currency'] }}</td>
                                    <td>{{ $bal['date'] }}</td>
                                    <td>
                                        @if($acc['currency'] === 'USD')
                                            ${{ number_format($bal['balance']/100, 2) }}
                                        @else
                                            {{ number_format($bal['balance']/100, 2) }} UZS
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Kassa qoldiqlari topilmadi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($type === 'debt_registry')
            <div class="skeuo-table-wrapper">
                <table class="skeuo-table skeuo-table-paper">
                    <thead>
                        <tr>
                            <th>Kontragent</th>
                            <th>Turi</th>
                            <th>Qoldiq (USD)</th>
                            <th>Qoldiq (UZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['entries'] ?? [] as $entry)
                            <tr>
                                <td><strong>{{ $entry['counterparty_name'] }}</strong></td>
                                <td>{{ $entry['type'] }}</td>
                                <td>
                                    <span class="{{ $entry['outstanding_usd'] > 0 ? 'text-green' : ($entry['outstanding_usd'] < 0 ? 'text-red' : 'text-muted') }}">
                                        ${{ number_format($entry['outstanding_usd']/100, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $entry['outstanding_uzs'] > 0 ? 'text-green' : ($entry['outstanding_uzs'] < 0 ? 'text-red' : 'text-muted') }}">
                                        {{ number_format($entry['outstanding_uzs']/100, 2) }} UZS
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Qarzlar vaqtinchalik mavjud emas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($type === 'category_breakdown')
            <div class="skeuo-table-wrapper">
                <table class="skeuo-table skeuo-table-paper">
                    <thead>
                        <tr>
                            <th>Kategoriya</th>
                            <th>Jami USD</th>
                            <th>Jami UZS</th>
                            <th>Tranzaksiyalar soni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['categories'] ?? [] as $cat)
                            <tr>
                                <td><strong>{{ $cat['category'] }}</strong></td>
                                <td class="text-green">${{ number_format($cat['total_usd']/100, 2) }}</td>
                                <td class="text-green">{{ number_format($cat['total_uzs']/100, 2) }} UZS</td>
                                <td>{{ $cat['transaction_count'] }} ta</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Kategoriyalar bo'yicha ma'lumot topilmadi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

@endsection