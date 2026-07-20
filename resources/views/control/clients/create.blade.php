@extends('control.layout')

@section('title', 'Yangi mijoz qo\'shish')

@section('content')
<div class="container-fluid" style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Yangi mijoz qo'shish</h2>
        <p class="text-muted">Mijoz kartochkasini yaratish</p>
    </div>

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="skeuo-card">
        <form method="POST" action="{{ route('control.clients.store') }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            <div class="form-group">
                <label class="form-label" for="company_name">Kompaniya nomi (Yuridik shaxs):</label>
                <input type="text" id="company_name" name="company_name" class="skeuo-input" placeholder="Masalan: IT-Cloud LLC" required value="{{ old('company_name') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_name">Mas'ul shaxs ismi familiyasi:</label>
                <input type="text" id="contact_name" name="contact_name" class="skeuo-input" placeholder="Masalan: Nilufar PIN" required value="{{ old('contact_name') }}">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="phone">Telefon:</label>
                    <input type="text" id="phone" name="phone" class="skeuo-input" placeholder="+998901234567" value="{{ old('phone') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="telegram">Telegram username:</label>
                    <input type="text" id="telegram" name="telegram" class="skeuo-input" placeholder="@nilufar_pin" value="{{ old('telegram') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">E-mail:</label>
                <input type="email" id="email" name="email" class="skeuo-input" placeholder="email@misol.uz" value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Manzil:</label>
                <input type="text" id="address" name="address" class="skeuo-input" placeholder="Toshkent sh., Chilonzor tumani" value="{{ old('address') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Izoh (Notes):</label>
                <textarea id="notes" name="notes" class="skeuo-input" style="height: 80px;" placeholder="Mijoz haqida maxsus eslatmalar...">{{ old('notes') }}</textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary" style="flex: 1;">Saqlash</button>
                <a href="{{ route('control.clients.index') }}" class="skeuo-btn text-muted" style="text-align: center;">Bekor qilish</a>
            </div>
        </form>
    </div>
</div>
@endsection
