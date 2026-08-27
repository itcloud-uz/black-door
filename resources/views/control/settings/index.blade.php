@extends('control.layout')

@section('title', 'Tizim Sozlamalari')

@section('content')
<div class="container-fluid" style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: var(--text-primary);">Tizim Sozlamalari</h2>
            <p class="text-muted">Mobil ilova logosi, kontaktlar va global tizim sozlamalari</p>
        </div>
    </div>

    @if(session('success'))
        <div class="skeuo-alert skeuo-alert-success mb-md">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="skeuo-alert skeuo-alert-danger mb-md">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('control.settings.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid-2 mb-xl">
            {{-- Branding & Logo Card --}}
            <div class="skeuo-card" style="grid-column: span 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="skeuo-card-header mb-md">
                        <h3 class="skeuo-card-title"><i class="bi bi-image text-primary"></i> Brending va Logotip</h3>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 24px; padding: 20px; background: var(--bg-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-pressed-sm);">
                        <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 8px;">Joriy logotip:</p>
                        <img src="{{ file_exists(public_path('branding/custom_logo_vertical.png')) ? asset('branding/custom_logo_vertical.png') . '?v=' . filemtime(public_path('branding/custom_logo_vertical.png')) : asset('branding/logo_vertical.png') . '?v=' . filemtime(public_path('branding/logo_vertical.png')) }}" 
                             alt="Current Logo" 
                             style="max-width: 140px; height: auto; border-radius: var(--radius-md); box-shadow: var(--shadow-neutral-sm);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="logo">Yangi logotip yuklash (PNG, JPG, JPEG):</label>
                        <input type="file" id="logo" name="logo" class="skeuo-input" accept="image/*">
                        <small class="text-muted" style="margin-top: 6px; display: block; font-size: 0.75rem; line-height: 1.3;">
                            * Diqqat! Tizim yuklangan logotipni qayta ishlab, mobil ilova ikonkalari, splash ekranlar va yuz tanish himoya doirasini avtomatik yangilaydi.
                        </small>
                    </div>
                </div>
            </div>

            {{-- System & Contacts Card --}}
            <div class="skeuo-card" style="grid-column: span 1;">
                <div class="skeuo-card-header mb-md">
                    <h3 class="skeuo-card-title"><i class="bi bi-sliders text-primary"></i> Tizim sozlamalari</h3>
                </div>

                <div class="form-group mb-md">
                    <label class="form-label" for="company_name">Tizim nomi:</label>
                    <input type="text" id="company_name" name="company_name" class="skeuo-input" required value="{{ old('company_name', $companyName) }}">
                </div>

                <div class="form-group mb-md">
                    <label class="form-label" for="company_tagline">Tizim shiori:</label>
                    <input type="text" id="company_tagline" name="company_tagline" class="skeuo-input" required value="{{ old('company_tagline', $companyTagline) }}">
                </div>

                <div class="form-group mb-md">
                    <label class="form-label" for="accent_color">Tizim asosiy rangi (Accent Color):</label>
                    <select name="accent_color" id="accent_color" class="skeuo-input" required>
                        <option value="green" {{ $accentColor === 'green' ? 'selected' : '' }}>Yashil (Muted Mint)</option>
                        <option value="blue" {{ $accentColor === 'blue' ? 'selected' : '' }}>Ko'k (Modern Ocean)</option>
                        <option value="red" {{ $accentColor === 'red' ? 'selected' : '' }}>Qizil (Deep Crimson)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Contact Info Card --}}
        <div class="skeuo-card mb-xl">
            <div class="skeuo-card-header mb-md">
                <h3 class="skeuo-card-title"><i class="bi bi-telephone text-primary"></i> Mijozlar uchun aloqa kontaktlari</h3>
            </div>
            
            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="support_phone">Telefon raqami:</label>
                    <input type="text" id="support_phone" name="support_phone" class="skeuo-input" required value="{{ old('support_phone', $supportPhone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="support_email">Gmail manzili:</label>
                    <input type="email" id="support_email" name="support_email" class="skeuo-input" required value="{{ old('support_email', $supportEmail) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="support_telegram">Telegram aloqa manzili (@username):</label>
                    <input type="text" id="support_telegram" name="support_telegram" class="skeuo-input" required value="{{ old('support_telegram', $supportTelegram) }}">
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="submit" class="skeuo-btn skeuo-btn-primary" style="padding: 12px 36px; font-weight: 700; font-size: 0.95rem;">
                <i class="bi bi-check-circle"></i> Sozlamalarni saqlash
            </button>
        </div>
    </form>

    <div class="skeuo-card mt-xl" style="margin-top: 24px;">
        <div class="skeuo-card-header mb-md">
            <h3 class="skeuo-card-title"><i class="bi bi-phone-fill text-primary"></i> Mobil ilovani qayta qurish (APK Build)</h3>
        </div>
        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">
            Yangi logotip yuklaganingizdan so'ng, telefonlardagi ilova ikonkalari va rasmlarini to'liq yangilash uchun mobil ilovani serverda/kompyuterda qayta qurishingiz (build) lozim.
        </p>
        <form method="POST" action="{{ route('control.settings.rebuild-apk') }}">
            @csrf
            <button type="submit" class="skeuo-btn skeuo-btn-secondary" style="padding: 12px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <i class="bi bi-cpu"></i> Ilovani qaytadan qurish (Build APK)
            </button>
        </form>
    </div>
</div>
@endsection
