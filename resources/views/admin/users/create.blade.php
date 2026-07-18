@extends('layouts.app')

@section('title', 'Yangi foydalanuvchi')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><a href="{{ route('admin.users.index') }}">Foydalanuvchilar</a></li>
    <li><span class="current">Yangi</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title">➕ Yangi foydalanuvchi</h1>
    <a href="{{ route('admin.users.index') }}" class="skeuo-btn skeuo-btn-sm">← Ortga</a>
</div>

<div class="skeuo-card" style="max-width: 700px;" x-data="dynamicForm">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="name">Ism</label>
                <input type="text" id="name" name="name" class="skeuo-input"
                       value="{{ old('name') }}" placeholder="To'liq ism" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="skeuo-input"
                       value="{{ old('email') }}" placeholder="email@misol.uz" required>
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="phone">Telefon</label>
                <input type="text" id="phone" name="phone" class="skeuo-input"
                       value="{{ old('phone') }}" placeholder="+998 90 123 45 67">
                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Parol</label>
                <input type="password" id="password" name="password" class="skeuo-input"
                       placeholder="Kamida 8 belgi" required>
                @error('password') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="role">Rol</label>
            <select id="role" name="role" class="skeuo-select" required
                    x-model="selectedRole">
                <option value="">— Tanlang —</option>
                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="financier" {{ old('role') === 'financier' ? 'selected' : '' }}>Moliyachi</option>
                <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Menejer</option>
                <option value="employee" {{ old('role') === 'employee' ? 'selected' : '' }}>Xodim</option>
            </select>
            @error('role') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Manager → Object Selector --}}
        <div class="form-group" x-show="showObjectSelector" x-transition>
            <label class="form-label" for="object_id">Obyekt</label>
            <select id="object_id" name="object_id" class="skeuo-select">
                <option value="">— Obyekt tanlang —</option>
                @foreach($objects ?? [] as $object)
                    <option value="{{ $object->id }}" {{ old('object_id') == $object->id ? 'selected' : '' }}>
                        {{ $object->type->label() }} — {{ $object->name }}
                    </option>
                @endforeach
            </select>
            <span class="form-hint">Menejerga biriktiladigan obyekt</span>
            @error('object_id') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Financier → PIN Code --}}
        <div class="form-group" x-show="showPinField" x-transition>
            <label class="form-label" for="pin_code">PIN kod (4 raqam)</label>
            <input type="text" id="pin_code" name="pin_code" class="skeuo-input"
                   maxlength="4" pattern="\d{4}"
                   placeholder="●●●●" style="max-width: 160px; letter-spacing: 8px; text-align: center; font-family: var(--font-mono);">
            <span class="form-hint">Moliya moduliga kirish uchun PIN kod</span>
            @error('pin_code') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div style="border-top: 1px solid rgba(184,115,51,0.1); padding-top: var(--space-lg); margin-top: var(--space-lg);">
            <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg">
                💾 Saqlash
            </button>
        </div>
    </form>
</div>

@endsection
