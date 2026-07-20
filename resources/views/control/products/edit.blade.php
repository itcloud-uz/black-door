@extends('control.layout')

@section('title', 'Mahsulotni tahrirlash')

@section('content')
<div class="container-fluid" style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Mahsulotni tahrirlash</h2>
        <p class="text-muted">Katalogdagi dastur ma'lumotlarini yangilash</p>
    </div>

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="skeuo-card">
        <form method="POST" action="{{ route('control.products.update', $product->id) }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="name">Mahsulot nomi:</label>
                <input type="text" id="name" name="name" class="skeuo-input" required value="{{ old('name', $product->name) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="code">Mahsulot kodi (Slug, unique):</label>
                <input type="text" id="code" name="code" class="skeuo-input" required value="{{ old('code', $product->code) }}">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Ushbu kod klient moduli litsenziyani tekshirishida ishlatiladi.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Tavsif (Description):</label>
                <textarea id="description" name="description" class="skeuo-input" style="height: 100px;">{{ old('description', $product->description) }}</textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary" style="flex: 1;">Saqlash</button>
                <a href="{{ route('control.products.show', $product->id) }}" class="skeuo-btn text-muted" style="text-align: center;">Bekor qilish</a>
            </div>
        </form>
    </div>
</div>
@endsection
