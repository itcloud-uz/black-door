@extends('layouts.app')

@section('title', 'Xodimlar')

@section('breadcrumb')
    <li><a href="{{ route('manager.dashboard') }}">Bosh sahifa</a></li>
    <li><span class="current">Xodimlar</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">👥 Obyekt Xodimlari boshqaruvi</h1>
</div>

<div class="grid-3mb" style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-lg); margin-bottom: var(--space-xl);">
    {{-- Form --}}
    <div class="skeuo-card">
        <div class="skeuo-card-header">
            <h3 class="skeuo-card-title">➕ Yangi Xodim qo'shish</h3>
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

            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="width: 100%;">💾 Saqlash</button>
        </form>
    </div>

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
                        <th>Holat</th>
                        <th>Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees ?? [] as $emp)
                        <tr>
                            <td>
                                <strong>{{ $emp->user->name ?? '—' }}</strong>
                                <div class="text-xs text-muted">{{ $emp->user->phone ?? '—' }}</div>
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
                            <td>
                                <span class="skeuo-badge {{ $emp->is_active ? 'skeuo-badge-green' : 'skeuo-badge-red' }}">
                                    {{ $emp->is_active ? 'Faol' : 'Nofaol' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('manager.employees.toggle-active', $emp->id) }}" style="display: inline;">
                                    @csrf
                                    @if($emp->is_active)
                                        <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-danger">🚫 Faolsizlantirish</button>
                                    @else
                                        <button type="submit" class="skeuo-btn skeuo-btn-sm skeuo-btn-success">✅ Faollashtirish</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted p-xl">Xodimlar mavjud emas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection