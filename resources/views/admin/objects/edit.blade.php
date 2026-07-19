@extends('layouts.app')

@section('title', 'Obyektni tahrirlash')

@section('breadcrumb')
    <li><a href="{{ route('admin.dashboard') }}">Bosh sahifa</a></li>
    <li><a href="{{ route('admin.objects.index') }}">Obyektlar</a></li>
    <li><a href="{{ route('admin.objects.show', $object->id) }}">{{ $object->name }}</a></li>
    <li><span class="current">Tahrirlash</span></li>
@endsection

@section('content')

<div class="page-header">
    <h1 class="page-title"><i class="bi bi-pencil-square"></i> Obyektni tahrirlash</h1>
    <a href="{{ route('admin.objects.show', $object->id) }}" class="skeuo-btn skeuo-btn-sm">← Ortga</a>
</div>

<div class="skeuo-card" style="max-width: 700px;" x-data="{ selectedType: '{{ $object->type->value }}' }">
    <form method="POST" action="{{ route('admin.objects.update', $object->id) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label class="form-label" for="name">Nomi</label>
            <input type="text" id="name" name="name" class="skeuo-input"
                   value="{{ old('name', $object->name) }}" placeholder="Obyekt nomi" required>
            @error('name') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="type">Turi</label>
            <select id="type" name="type" class="skeuo-select" required x-model="selectedType">
                <option value="">— Tanlang —</option>
                <option value="factory" {{ old('type', $object->type->value) === 'factory' ? 'selected' : '' }}>Zavod</option>
                <option value="construction" {{ old('type', $object->type->value) === 'construction' ? 'selected' : '' }}>Qurilish</option>
                <option value="warehouse" {{ old('type', $object->type->value) === 'warehouse' ? 'selected' : '' }}>Ombor</option>
            </select>
            @error('type') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Manzil</label>
            <input type="text" id="address" name="address" class="skeuo-input"
                   value="{{ old('address', $object->address) }}" placeholder="Obyekt manzili">
            @error('address') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="manager_id">Menejer</label>
            <select id="manager_id" name="manager_id" class="skeuo-select">
                <option value="">— Menejer tanlang (ixtiyoriy) —</option>
                @foreach($managers ?? [] as $manager)
                    <option value="{{ $manager->id }}" {{ old('manager_id', $object->activeManager->user_id ?? '') == $manager->id ? 'selected' : '' }}>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
            @error('manager_id') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="note">Izoh</label>
            <textarea id="note" name="note" class="skeuo-input" placeholder="Qo'shimcha ma'lumot...">{{ old('note', $object->note) }}</textarea>
            @error('note') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="is_active">Holati</label>
            <select id="is_active" name="is_active" class="skeuo-select" required>
                <option value="1" {{ old('is_active', $object->is_active) ? 'selected' : '' }}>Faol</option>
                <option value="0" {{ old('is_active', $object->is_active) ? '' : 'selected' }}>Nofaol</option>
            </select>
            @error('is_active') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div style="border-top: 1px solid rgba(184,115,51,0.1); padding-top: var(--space-lg); margin-top: var(--space-lg);">
            <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg">
                <i class="bi bi-save"></i> O'zgarishlarni saqlash
            </button>
        </div>
    </form>
</div>

@endsection
