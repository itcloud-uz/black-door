@extends('layouts.app')

@section('title', 'Foydalanuvchini tahrirlash')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><a href="{{ route('admin.users.index') }}">Foydalanuvchilar</a></li>
    <li><span class="current">Tahrirlash</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-pencil-square"></i> Foydalanuvchini tahrirlash</h1>
    <a href="{{ route('admin.users.index') }}" class="skeuo-btn skeuo-btn-sm">← Ortga</a>
</div>

<div class="skeuo-card" style="max-width: 700px;" 
     x-data="{ 
         selectedRole: '{{ old('role', $user->role->value) }}',
         get showObjectSelector() { return this.selectedRole === 'manager'; },
         get showPinField() { return this.selectedRole === 'financier'; }
     }">
     
    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="name">Ism</label>
                <input type="text" id="name" name="name" class="skeuo-input"
                       value="{{ old('name', $user->name) }}" placeholder="To'liq ism" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="skeuo-input"
                       value="{{ old('email', $user->email) }}" placeholder="email@misol.uz" required>
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="phone">Telefon</label>
                <input type="text" id="phone" name="phone" class="skeuo-input"
                       value="{{ old('phone', $user->phone) }}" placeholder="+998 90 123 45 67">
                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Yangi Parol (ixtiyoriy)</label>
                <input type="password" id="password" name="password" class="skeuo-input"
                       placeholder="O'zgartirmaslik uchun bo'sh qoldiring">
                @error('password') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="role">Rol</label>
            <select id="role" name="role" class="skeuo-select" required x-model="selectedRole">
                <option value="">— Tanlang —</option>
                <option value="super_admin" {{ old('role', $user->role->value) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="financier" {{ old('role', $user->role->value) === 'financier' ? 'selected' : '' }}>Moliyachi</option>
                <option value="manager" {{ old('role', $user->role->value) === 'manager' ? 'selected' : '' }}>Menejer</option>
                <option value="employee" {{ old('role', $user->role->value) === 'employee' ? 'selected' : '' }}>Xodim</option>
            </select>
            @error('role') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Manager → Object Selector --}}
        <div class="form-group" x-show="showObjectSelector" x-transition>
            <label class="form-label" for="object_id">Obyekt</label>
            <select id="object_id" name="object_id" class="skeuo-select">
                <option value="">— Obyekt tanlang —</option>
                @foreach($objects ?? [] as $object)
                    <option value="{{ $object->id }}" {{ old('object_id', $user->object_id) == $object->id ? 'selected' : '' }}>
                        {{ $object->type->label() }} — {{ $object->name }}
                    </option>
                @endforeach
            </select>
            <span class="form-hint">Menejerga biriktiladigan obyekt</span>
            @error('object_id') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Financier → PIN Code --}}
        <div class="form-group" x-show="showPinField" x-transition>
            <label class="form-label" for="pin_code">Yangi PIN kod (4 raqam - ixtiyoriy)</label>
            <input type="text" id="pin_code" name="pin_code" class="skeuo-input"
                   maxlength="4" pattern="\d{4}"
                   placeholder="●●●●" style="max-width: 160px; letter-spacing: 8px; text-align: center; font-family: var(--font-mono);">
            <span class="form-hint">O'zgartirmaslik uchun bo'sh qoldiring</span>
            @error('pin_code') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div style="border-top: 1px solid rgba(184,115,51,0.1); padding-top: var(--space-lg); margin-top: var(--space-lg);">
            <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg">
                <i class="bi bi-save"></i> O'zgarishlarni saqlash
            </button>
        </div>
    </form>
</div>

@endsection
