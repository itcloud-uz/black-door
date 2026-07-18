@extends('layouts.finance')

@section('title', 'Yangi kassa')

@section('breadcrumb')
    <li><a href="{{ route('finance.dashboard') }}">Moliya</a></li>
    <li><a href="{{ route('finance.cash-accounts.index') }}">Kassalar</a></li>
    <li><span class="current">Yangi</span></li>
@endsection

@section('finance-content')

<h2 class="handwriting-title mb-lg">Yangi kassa ochish</h2>

<div style="max-width: 500px;">
    <form method="POST" action="{{ route('finance.cash-accounts.store') }}">
        @csrf

        <div class="form-group">
            <label class="form-label form-label-paper" for="name">Kassa nomi</label>
            <input type="text" id="name" name="name" class="skeuo-input skeuo-input-paper"
                   value="{{ old('name') }}" placeholder="Asosiy kassa" required>
            @error('name') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label form-label-paper" for="type">Turi</label>
            <select id="type" name="type" class="skeuo-select skeuo-select-paper" required>
                <option value="">— Tanlang —</option>
                <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>💵 Naqd pul</option>
                <option value="bank" {{ old('type') === 'bank' ? 'selected' : '' }}>🏦 Bank hisob</option>
                <option value="card" {{ old('type') === 'card' ? 'selected' : '' }}>💳 Karta</option>
                <option value="safe" {{ old('type') === 'safe' ? 'selected' : '' }}>🔐 Seyf</option>
                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>💰 Boshqa</option>
            </select>
            @error('type') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label form-label-paper" for="note">Izoh</label>
            <textarea id="note" name="note" class="skeuo-input skeuo-input-paper" placeholder="Qo'shimcha ma'lumot...">{{ old('note') }}</textarea>
        </div>

        <div style="border-top: 1px dashed var(--paper-line); padding-top: 16px; margin-top: 16px;">
            <button type="submit" class="skeuo-btn skeuo-btn-primary">
                💾 Saqlash
            </button>
            <a href="{{ route('finance.cash-accounts.index') }}" class="skeuo-btn" style="margin-left: 8px;">
                Bekor qilish
            </a>
        </div>
    </form>
</div>

@endsection
