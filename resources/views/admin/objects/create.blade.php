@extends('layouts.app')

@section('title', 'Yangi obyekt')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><a href="{{ route('admin.objects.index') }}">Obyektlar</a></li>
    <li><span class="current">Yangi</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-plus-lg"></i> Yangi obyekt</h1>
    <a href="{{ route('admin.objects.index') }}" class="skeuo-btn skeuo-btn-sm">← Ortga</a>
</div>

<div class="skeuo-card" style="max-width: 700px;" x-data="dynamicForm">
    <form method="POST" action="{{ route('admin.objects.store') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Nomi</label>
            <input type="text" id="name" name="name" class="skeuo-input"
                   value="{{ old('name') }}" placeholder="Obyekt nomi" required>
            @error('name') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="type">Turi</label>
            <select id="type" name="type" class="skeuo-select" required x-model="selectedType">
                <option value="">— Tanlang —</option>
                <option value="factory" {{ old('type') === 'factory' ? 'selected' : '' }}><i class="bi bi-building-gear"></i> Zavod</option>
                <option value="construction" {{ old('type') === 'construction' ? 'selected' : '' }}><i class="bi bi-cone-striped"></i> Qurilish</option>
                <option value="warehouse" {{ old('type') === 'warehouse' ? 'selected' : '' }}><i class="bi bi-shop"></i> Ombor</option>
            </select>
            @error('type') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Manzil</label>
            <input type="text" id="address" name="address" class="skeuo-input"
                   value="{{ old('address') }}" placeholder="Obyekt manzili">
            @error('address') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="manager_id">Menejer</label>
            <select id="manager_id" name="manager_id" class="skeuo-select">
                <option value="">— Menejer tanlang (ixtiyoriy) —</option>
                @foreach($managers ?? [] as $manager)
                    <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
            @error('manager_id') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="note">Izoh</label>
            <textarea id="note" name="note" class="skeuo-input" placeholder="Qo'shimcha ma'lumot...">{{ old('note') }}</textarea>
            @error('note') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div style="border-top: 1px solid rgba(184,115,51,0.1); padding-top: var(--space-lg); margin-top: var(--space-lg);">
            <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg">
                <i class="bi bi-save"></i> Saqlash
            </button>
        </div>
    </form>
</div>

@endsection
