@extends('control.layout')

@section('title', 'Mijozni tahrirlash')

@section('content')
<div class="container-fluid" style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Mijozni tahrirlash</h2>
        <p class="text-muted">Mijoz ma'lumotlarini yangilash</p>
    </div>

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="skeuo-card">
        <form method="POST" action="{{ route('control.clients.update', $client->id) }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="company_name">Kompaniya nomi (Yuridik shaxs):</label>
                <input type="text" id="company_name" name="company_name" class="skeuo-input" required value="{{ old('company_name', $client->company_name) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_name">Mas'ul shaxs ismi familiyasi:</label>
                <input type="text" id="contact_name" name="contact_name" class="skeuo-input" required value="{{ old('contact_name', $client->contact_name) }}">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="phone">Telefon:</label>
                    <input type="text" id="phone" name="phone" class="skeuo-input" value="{{ old('phone', $client->phone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="telegram">Telegram username:</label>
                    <input type="text" id="telegram" name="telegram" class="skeuo-input" value="{{ old('telegram', $client->telegram) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">E-mail:</label>
                <input type="email" id="email" name="email" class="skeuo-input" value="{{ old('email', $client->email) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Manzil:</label>
                <input type="text" id="address" name="address" class="skeuo-input" value="{{ old('address', $client->address) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Izoh (Notes):</label>
                <textarea id="notes" name="notes" class="skeuo-input" style="height: 80px;">{{ old('notes', $client->notes) }}</textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" class="skeuo-btn skeuo-btn-primary" style="flex: 1;">Saqlash</button>
                <a href="{{ route('control.clients.show', $client->id) }}" class="skeuo-btn text-muted" style="text-align: center;">Bekor qilish</a>
            </div>
        </form>
    </div>
</div>
@endsection
