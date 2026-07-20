<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Black Door — Tizimni faollashtirish</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ \App\Models\Setting::get('theme_css_version', '1') }}">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--space-md);
            background-color: var(--bg-color);
            font-family: var(--font-body);
        }

        .activate-container {
            width: 100%;
            max-width: 500px;
        }

        .activate-card {
            background: var(--surface);
            border: none;
            border-radius: var(--radius-xl);
            padding: var(--space-2xl) var(--space-xl);
            box-shadow: var(--shadow-raised);
        }

        .activate-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }

        .divider {
            height: 2px;
            background: var(--shadow-dark);
            opacity: 0.4;
            margin: var(--space-md) 0;
        }

        .error-box {
            background: var(--surface);
            border: 2px solid var(--danger);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            color: var(--danger);
            font-size: 0.85rem;
            text-align: center;
            box-shadow: var(--shadow-pressed-sm);
            font-weight: 600;
            margin-bottom: var(--space-md);
        }

        .uuid-box {
            background: var(--bg-color);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-primary);
            text-align: center;
            box-shadow: var(--shadow-pressed-sm);
            user-select: all;
            margin-bottom: var(--space-md);
        }
    </style>
</head>
<body>
    <div class="activate-container">
        <div class="activate-card">
            {{-- Header --}}
            <div class="activate-header" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <img src="{{ file_exists(public_path('branding/custom_logo_vertical.png')) ? asset('branding/custom_logo_vertical.png') : asset('branding/logo_vertical.png') }}" alt="Black Door" style="max-width: 180px; height: auto;">
                <h2 style="font-size: 1.25rem; font-weight: 800; text-transform: uppercase; color: var(--text-primary); margin-top: 16px;">Tizimni faollashtirish</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">Ushbu o'rnatma uchun faollashtirish kalitini kiriting</p>
            </div>

            <div class="divider"></div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="error-box">
                    <span style="font-size: 1rem; margin-right: 4px;">✕</span>
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            {{-- UUID display --}}
            <div style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;">O'rnatma ID (Hardware UUID):</label>
                <div class="uuid-box">{{ $deviceUuid }}</div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -8px;">Ushbu ID litsenziya kalitini o'rnatmaga bog'lash uchun ishlatiladi.</p>
            </div>

            {{-- Activation Form --}}
            <form method="POST" action="{{ route('license.activate.submit') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="license_key" style="font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Litsenziya kaliti (License Key):</label>
                    <input
                        type="text"
                        id="license_key"
                        name="license_key"
                        class="skeuo-input"
                        placeholder="BD-XXXX-XXXX-XXXX-XXXX"
                        value="{{ old('license_key') }}"
                        required
                        autofocus
                        style="width: 100%; text-align: center; font-weight: bold; letter-spacing: 1px;"
                    >
                </div>

                <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg w-full" style="font-weight: bold;">
                    <i class="bi bi-shield-check"></i> Faollashtirish
                </button>
            </form>

            <a href="{{ route('login') }}" class="skeuo-btn skeuo-btn-lg w-full" style="font-weight: bold; display: flex; align-items: center; justify-content: center; text-decoration: none; margin-top: 12px; color: var(--text-primary);">
                <i class="bi bi-arrow-left" style="margin-right: 6px;"></i> Ortga (Kirish sahifasiga)
            </a>

            <div class="divider"></div>

            {{-- Contacts & info --}}
            <div style="background: var(--surface); padding: 16px; border-radius: var(--radius-md); box-shadow: var(--shadow-pressed-sm); text-align: center;">
                <h4 style="margin: 0 0 8px 0; font-size: 0.9rem; font-weight: 800; text-transform: uppercase;"><i class="bi bi-telephone"></i> Biz bilan bog'lanish</h4>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">Litsenziya sotib olish, uzaytirish yoki trial sinov rejimi so'rash uchun biz bilan bog'laning:</p>
                <div style="display: flex; flex-direction: column; gap: 8px; font-weight: bold; font-size: 0.85rem;">
                    <div><i class="bi bi-phone"></i> +998 91 187 37 30</div>
                    <div><i class="bi bi-envelope"></i> <a href="mailto:itclouduz@gmail.com" style="color: var(--text-primary); text-decoration: underline;">itclouduz@gmail.com</a></div>
                    <div><i class="bi bi-telegram"></i> <a href="https://t.me/ITclouduz_me" target="_blank" style="color: var(--text-primary); text-decoration: underline;">@ITclouduz_me</a></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
