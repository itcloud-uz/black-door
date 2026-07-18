@extends('layouts.manager')

@section('title', 'Xodimlar va To\'lovlar')

@section('breadcrumb')
    <li><a href="{{ route('manager.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Xodimlar</span></li>
@endsection

@section('manager-content')

@php
    $user = auth()->user();
    $isManager = $user->isManager();
    $permissions = $user->isEmployee() ? ($user->objectEmployee?->permissions ?? []) : [];
@endphp

<div x-data="{
    showEditModal: false,
    editForm: {
        id: '',
        name: '',
        email: '',
        phone: '',
        position: '',
        daily_rate: 0,
        daily_rate_currency: 'UZS',
        monthly_rate: 0,
        monthly_rate_currency: 'UZS',
        permissions: []
    },
    openEditModal(emp) {
        this.editForm.id = emp.id;
        this.editForm.name = emp.user ? emp.user.name : '';
        this.editForm.email = emp.user ? emp.user.email : '';
        this.editForm.phone = emp.user ? emp.user.phone || '' : '';
        this.editForm.position = emp.position;
        this.editForm.daily_rate = emp.daily_rate / 100;
        this.editForm.daily_rate_currency = emp.daily_rate_currency || 'UZS';
        this.editForm.monthly_rate = emp.monthly_rate / 100;
        this.editForm.monthly_rate_currency = emp.monthly_rate_currency || 'UZS';
        this.editForm.permissions = emp.permissions || [];
        this.showEditModal = true;
    },
    showPayModal: false,
    payForm: {
        employee_id: '',
        name: '',
        amount: '',
        currency: 'UZS',
        type: 'salary',
        period_start: '{{ now()->startOfMonth()->toDateString() }}',
        period_end: '{{ now()->endOfMonth()->toDateString() }}',
        object_cash_account_id: '',
        note: ''
    },
    openPayModal(emp) {
        this.payForm.employee_id = emp.id;
        this.payForm.name = emp.user ? emp.user.name : '';
        this.payForm.amount = emp.monthly_rate > 0 ? emp.monthly_rate / 100 : (emp.daily_rate > 0 ? (emp.daily_rate / 100) * 26 : '');
        this.payForm.currency = emp.monthly_rate > 0 ? emp.monthly_rate_currency : (emp.daily_rate > 0 ? emp.daily_rate_currency : 'UZS');
        this.showPayModal = true;
    }
}">

    <div class="page-header">
        <h1 class="page-title"><i class="bi bi-people"></i> Obyekt Xodimlari va Maoshlar</h1>
    </div>

    @if(session('error'))
        <div style="background: rgba(220,53,69,0.06); border: 1px dashed var(--accent-red); color: var(--accent-red); padding: 12px; border-radius: 6px; margin-bottom: 20px;" class="text-sm">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
        {{-- Form --}}
        @if($isManager)
            <div class="skeuo-card">
                <div class="skeuo-card-header">
                    <h3 class="skeuo-card-title"><i class="bi bi-plus-lg"></i> Yangi Xodim qo'shish</h3>
                </div>

                <form method="POST" action="{{ route('manager.employees.store') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">FIO (Ism familiya)</label>
                        <input type="text" name="name" class="skeuo-input" value="{{ old('name') }}" placeholder="Eshmatov Toshmat" required>
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email (tizimga kirish uchun)</label>
                        <input type="email" name="email" class="skeuo-input" value="{{ old('email') }}" placeholder="eshmat@misol.uz" required>
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="skeuo-input" value="{{ old('phone') }}" placeholder="+998 90 123 45 67">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Parol</label>
                        <input type="password" name="password" class="skeuo-input" placeholder="Kamida 8 belgi" required>
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lavozim</label>
                        <input type="text" name="position" class="skeuo-input" value="{{ old('position') }}" placeholder="Master, Ishchi..." required>
                        @error('position') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kunlik stavka</label>
                            <input type="number" name="daily_rate" step="0.01" min="0" class="skeuo-input" value="{{ old('daily_rate', 0) }}">
                        </div>
                        <div class="form-group" style="max-width: 100px;">
                            <label class="form-label">Valyuta</label>
                            <select name="daily_rate_currency" class="skeuo-select">
                                <option value="UZS">UZS</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Oylik stavka</label>
                            <input type="number" name="monthly_rate" step="0.01" min="0" class="skeuo-input" value="{{ old('monthly_rate', 0) }}">
                        </div>
                        <div class="form-group" style="max-width: 100px;">
                            <label class="form-label">Valyuta</label>
                            <select name="monthly_rate_currency" class="skeuo-select">
                                <option value="UZS">UZS</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ishga olingan sana</label>
                        <input type="date" name="hired_at" class="skeuo-input" value="{{ old('hired_at', now()->toDateString()) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ruxsatlar (Huquqlar)</label>
                        <div style="display: flex; flex-direction: column; gap: var(--space-xs); margin-top: 6px;">
                            <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                                <input type="checkbox" name="permissions[]" value="warehouse" checked>
                                <span><i class="bi bi-box-seam"></i> Ombor amallari</span>
                            </label>
                            <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                                <input type="checkbox" name="permissions[]" value="transactions">
                                <span><i class="bi bi-cash-stack"></i> Kassa amallari</span>
                            </label>
                            <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                                <input type="checkbox" name="permissions[]" value="employees">
                                <span><i class="bi bi-person-workspace"></i> Xodimlar boshqaruvi</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;"><i class="bi bi-save"></i> Saqlash</button>
                </form>
            </div>
        @else
            <div class="skeuo-card">
                <div class="skeuo-card-header">
                    <h3 class="skeuo-card-title"><i class="bi bi-info-circle"></i> Ma'lumot</h3>
                </div>
                <div class="p-md">
                    <p class="text-muted">Siz faqat xodimlar ro'yxatini ko'rish huquqiga egasiz. Yangi xodim qo'shish va ularni tahrirlash huquqi faqat Obyekt menejerida mavjud.</p>
                </div>
            </div>
        @endif

        {{-- List --}}
        <div class="skeuo-card">
            <div class="skeuo-card-header">
                <h3 class="skeuo-card-title">Xodimlar ro'yxati</h3>
            </div>

            <div class="skeuo-table-wrapper">
                <table class="skeuo-table">
                    <thead>
                        <tr>
                            <th>Ism</th>
                            <th>Lavozim</th>
                            <th>Kunlik stavka</th>
                            <th>Oylik stavka</th>
                            <th>Ishga kirgan sana</th>
                            <th>Ruxsatlar</th>
                            <th>Holat</th>
                            @if($isManager)
                                <th>Amallar</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees ?? [] as $emp)
                            <tr>
                                <td>
                                    <strong>{{ $emp->user->name ?? '—' }}</strong>
                                    <div class="text-xs text-muted">{{ $emp->user->phone ?? '—' }}</div>
                                    <div class="text-xs text-muted">{{ $emp->user->email ?? '—' }}</div>
                                </td>
                                <td>{{ $emp->position }}</td>
                                <td>
                                    @if($emp->daily_rate > 0)
                                        <x-amount-display :amount="$emp->daily_rate" :currency="$emp->daily_rate_currency->value" size="sm" />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($emp->monthly_rate > 0)
                                        <x-amount-display :amount="$emp->monthly_rate" :currency="$emp->monthly_rate_currency->value" size="sm" />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm">{{ $emp->hired_at ? $emp->hired_at->format('d.m.Y') : '—' }}</td>
                                <td class="text-sm">
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        @foreach($emp->permissions ?? [] as $p)
                                            <span class="skeuo-badge skeuo-badge-steel" style="font-size: 0.75rem;">
                                                @if($p === 'warehouse') <i class="bi bi-box-seam"></i> Ombor @elseif($p === 'transactions') <i class="bi bi-cash-stack"></i> Kassa @elseif($p === 'employees') <i class="bi bi-person-workspace"></i> Xodim @else {{ $p }} @endif
                                            </span>
                                        @endforeach
                                        @if(empty($emp->permissions))
                                            <span class="text-muted text-xs">yo'q</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="skeuo-badge {{ $emp->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                        {{ $emp->is_active ? 'Faol' : 'Nofaol' }}
                                    </span>
                                </td>
                                @if($isManager)
                                    <td>
                                        <div style="display: flex; gap: var(--space-xs);">
                                            <button type="button" class="skeuo-btn skeuo-btn-sm skeuo-btn-warning" @click="openEditModal({{ json_encode($emp) }})"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="skeuo-btn skeuo-btn-sm skeuo-btn-primary" @click="openPayModal({{ json_encode($emp) }})"><i class="bi bi-cash"></i> To'lov</button>
                                            
                                            <form method="POST" action="{{ route('manager.employees.toggle-active', $emp->id) }}" style="display: inline;">
                                                @csrf
                                                @if($emp->is_active)
                                                    <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-danger" title="Faolsizlantirish"><i class="bi bi-slash-circle"></i></button>
                                                @else
                                                    <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-success" title="Faollashtirish">✅</button>
                                                @endif
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isManager ? 8 : 7 }}" class="text-center text-muted p-xl">Xodimlar mavjud emas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Salary Payments Journal --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title"><i class="bi bi-cash"></i> Maoshlar va avanslar to'lovi jurnali</h3>
        </div>

        <div class="skeuo-table-wrapper">
            <table class="skeuo-table">
                <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Xodim</th>
                        <th>Lavozim</th>
                        <th>To'lov turi</th>
                        <th>Summa</th>
                        <th>Qamrov davri</th>
                        <th>Yozdi</th>
                        <th>Izoh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salaryPayments ?? [] as $pay)
                        <tr>
                            <td class="text-sm">{{ $pay->paid_at ? $pay->paid_at->format('d.m.Y H:i') : $pay->created_at->format('d.m.Y H:i') }}</td>
                            <td><strong>{{ $pay->employee->user->name ?? '—' }}</strong></td>
                            <td class="text-sm">{{ $pay->employee->position ?? '—' }}</td>
                            <td>
                                <span class="skeuo-badge {{ $pay->type === 'salary' ? 'skeuo-badge-green' : 'skeuo-badge-blue' }}">
                                    {{ $pay->type === 'salary' ? 'Ish haqi' : 'Avans' }}
                                </span>
                            </td>
                            <td>
                                <x-amount-display :amount="$pay->amount" :currency="$pay->currency->value" size="sm" />
                            </td>
                            <td class="text-sm">
                                {{ $pay->period_start ? $pay->period_start->format('d.m.Y') : '' }} - 
                                {{ $pay->period_end ? $pay->period_end->format('d.m.Y') : '' }}
                            </td>
                            <td class="text-sm">{{ $pay->creator->name ?? '—' }}</td>
                            <td class="text-sm text-muted">{{ $pay->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted p-lg">To'lovlar jurnali bo'sh.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($salaryPayments) && method_exists($salaryPayments, 'links'))
            <div class="skeuo-pagination mt-md">
                {{ $salaryPayments->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- EDIT MODAL --}}
    {{-- ========================================================================= --}}
    <div class="modal-overlay" x-show="showEditModal" x-transition style="display: none;">
        <div class="modal-content" @click.away="showEditModal = false">
            <div class="d-flex justify-between items-center mb-md" style="border-bottom: 1px solid rgba(184,115,51,0.1); padding-bottom: 8px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-pencil"></i> Xodim ma'lumotlarini tahrirlash</h3>
                <button type="button" class="skeuo-btn skeuo-btn-sm" @click="showEditModal = false" style="min-width: unset; padding: 4px 8px;">✕</button>
            </div>

            <form method="POST" :action="'/manager/employees/' + editForm.id">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">FIO</label>
                    <input type="text" name="name" class="skeuo-input" x-model="editForm.name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="skeuo-input" x-model="editForm.email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="skeuo-input" x-model="editForm.phone">
                </div>

                <div class="form-group">
                    <label class="form-label">Lavozim</label>
                    <input type="text" name="position" class="skeuo-input" x-model="editForm.position" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kunlik stavka</label>
                        <input type="number" name="daily_rate" step="0.01" min="0" class="skeuo-input" x-model="editForm.daily_rate">
                    </div>
                    <div class="form-group" style="max-width: 100px;">
                        <label class="form-label">Valyuta</label>
                        <select name="daily_rate_currency" class="skeuo-select" x-model="editForm.daily_rate_currency">
                            <option value="UZS">UZS</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Oylik stavka</label>
                        <input type="number" name="monthly_rate" step="0.01" min="0" class="skeuo-input" x-model="editForm.monthly_rate">
                    </div>
                    <div class="form-group" style="max-width: 100px;">
                        <label class="form-label">Valyuta</label>
                        <select name="monthly_rate_currency" class="skeuo-select" x-model="editForm.monthly_rate_currency">
                            <option value="UZS">UZS</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ruxsatlar (Huquqlar)</label>
                    <div style="display: flex; flex-direction: column; gap: var(--space-xs); margin-top: 6px;">
                        <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                            <input type="checkbox" name="permissions[]" value="warehouse" :checked="editForm.permissions.includes('warehouse')">
                            <span><i class="bi bi-box-seam"></i> Ombor amallari</span>
                        </label>
                        <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                            <input type="checkbox" name="permissions[]" value="transactions" :checked="editForm.permissions.includes('transactions')">
                            <span><i class="bi bi-cash-stack"></i> Kassa amallari</span>
                        </label>
                        <label class="d-flex items-center gap-xs cursor-pointer text-sm">
                            <input type="checkbox" name="permissions[]" value="employees" :checked="editForm.permissions.includes('employees')">
                            <span><i class="bi bi-person-workspace"></i> Xodimlar boshqaruvi</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-sm); margin-top: var(--space-md);">
                    <button type="submit" class="skeuo-btn skeuo-btn-success" style="flex: 1;"><i class="bi bi-save"></i> Yangilash</button>
                    <button type="button" class="skeuo-btn" @click="showEditModal = false" style="flex: 1;">Bekor qilish</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PAY SALARY MODAL --}}
    {{-- ========================================================================= --}}
    <div class="modal-overlay" x-show="showPayModal" x-transition style="display: none;">
        <div class="modal-content" @click.away="showPayModal = false">
            <div class="d-flex justify-between items-center mb-md" style="border-bottom: 1px solid rgba(184,115,51,0.1); padding-bottom: 8px;">
                <h3 style="margin: 0; font-size: 1.25rem;"><i class="bi bi-cash"></i> To'lov qilish — <span x-text="payForm.name"></span></h3>
                <button type="button" class="skeuo-btn skeuo-btn-sm" @click="showPayModal = false" style="min-width: unset; padding: 4px 8px;">✕</button>
            </div>

            <form method="POST" :action="'/manager/employees/' + payForm.employee_id + '/pay'">
                @csrf

                <div class="form-group">
                    <label class="form-label">Kassa (Qaysi kassadan to'lanadi)</label>
                    <select name="object_cash_account_id" class="skeuo-select" x-model="payForm.object_cash_account_id" required>
                        <option value="">— Tanlang —</option>
                        @foreach($cashAccounts ?? [] as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">To'lov turi</label>
                    <select name="type" class="skeuo-select" x-model="payForm.type" required>
                        <option value="salary">Oylik maosh (Salary)</option>
                        <option value="advance">Avans (Advance)</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">To'lov summasi</label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="skeuo-input" x-model="payForm.amount" required>
                    </div>
                    <div class="form-group" style="max-width: 100px;">
                        <label class="form-label">Valyuta</label>
                        <select name="currency" class="skeuo-select" x-model="payForm.currency" required>
                            <option value="UZS">UZS</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Davr boshlanishi</label>
                        <input type="date" name="period_start" class="skeuo-input" x-model="payForm.period_start" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Davr tugashi</label>
                        <input type="date" name="period_end" class="skeuo-input" x-model="payForm.period_end" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Izoh</label>
                    <textarea name="note" class="skeuo-input" x-model="payForm.note" placeholder="Masalan: 2026-yil iyul oyi uchun maosh"></textarea>
                </div>

                <div style="display: flex; gap: var(--space-sm); margin-top: var(--space-md);">
                    <button type="submit" class="skeuo-btn skeuo-btn-success" style="flex: 1;">✅ To'lovni tasdiqlash</button>
                    <button type="button" class="skeuo-btn" @click="showPayModal = false" style="flex: 1;">Bekor qilish</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('styles')
<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}
.modal-content {
    background: var(--bg-color, #e0e0e0);
    border-radius: 12px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    box-shadow: 8px 8px 16px rgba(0,0,0,0.15), -8px -8px 16px rgba(255,255,255,0.8);
    border: 1px solid rgba(184,115,51,0.1);
}
</style>
@endpush

@endsection