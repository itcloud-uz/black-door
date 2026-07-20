@extends('control.layout')

@section('title', 'Yangi mahsulot qo\'shish')

@section('content')
<div class="container-fluid" style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Yangi mahsulot qo'shish</h2>
        <p class="text-muted">Katalogga yangi sotiladigan dasturiy ta'minotni kiritish</p>
    </div>

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="skeuo-card">
        <form method="POST" action="{{ route('control.products.store') }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Mahsulot nomi:</label>
                <input type="text" id="name" name="name" class="skeuo-input" placeholder="Masalan: Black Door Enterprise" required value="{{ old('name') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="code">Mahsulot kodi (Slug, unique):</label>
                <input type="text" id="code" name="code" class="skeuo-input" placeholder="Masalan: blackdoor" required value="{{ old('code') }}">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Ushbu kod klient moduli litsenziyani tekshirishida ishlatiladi.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Tavsif (Description):</label>
                <textarea id="description" name="description" class="skeuo-input" style="height: 100px;" placeholder="Dastur haqida batafsil ma'lumot...">{{ old('description') }}</textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary" style="flex: 1;">Saqlash</button>
                <a href="{{ route('control.products.index') }}" class="skeuo-btn text-muted" style="text-align: center;">Bekor qilish</a>
            </div>
        </form>
    </div>
</div>
@endsection
