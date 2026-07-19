<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ \App\Models\Setting::get('company_name', 'Black Door') }} — Tizimga kirish">
    <title>{{ \App\Models\Setting::get('company_name', 'Black Door') }} — Kirish</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Theme Accent Custom Override --}}
    <style>
        :root {
            @php
                $accent = \App\Models\Setting::get('accent_color', 'green');
                if ($accent === 'blue') {
                    echo '--accent-green: var(--accent-blue) !important; ';
                    echo '--accent-green-start: var(--accent-blue-start) !important; ';
                    echo '--accent-green-end: var(--accent-blue-end) !important; ';
                } elseif ($accent === 'red') {
                    echo '--accent-green: var(--accent-red) !important; ';
                    echo '--accent-green-start: var(--accent-red-start) !important; ';
                    echo '--accent-green-end: var(--accent-red-end) !important; ';
                }
            @endphp
        }
    </style>

    {{-- Favicons --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

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

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: var(--surface);
            border: none;
            border-radius: var(--radius-xl);
            padding: var(--space-2xl) var(--space-xl);
            box-shadow: var(--shadow-raised);
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }

        .login-knocker {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto var(--space-lg);
            position: relative;
            background: var(--surface);
            box-shadow: var(--shadow-raised);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-knocker::before {
            content: '<i class="bi bi-key"></i>';
            font-size: 2rem;
        }

        .login-knocker::after {
            display: none;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .login-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: var(--space-lg);
        }

        .login-divider {
            height: 2px;
            background: var(--shadow-dark);
            opacity: 0.4;
            margin: var(--space-sm) 0;
        }

        .login-remember {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .login-error {
            background: var(--surface);
            border: 2px solid var(--danger);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            color: var(--danger);
            font-size: 0.85rem;
            text-align: center;
            box-shadow: var(--shadow-pressed-sm);
            font-weight: 600;
        }

        .login-error .ink-stamp {
            margin-right: var(--space-sm);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            {{-- Header --}}
            <div class="login-header" style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 24px;">
                <img src="{{ file_exists(public_path('branding/custom_logo_vertical.png')) ? asset('branding/custom_logo_vertical.png') : asset('branding/logo_vertical.png') }}" alt="Black Door" style="max-width: 180px; height: auto;">
            </div>

            <div class="login-divider"></div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="login-error">
                    <span class="ink-stamp ink-stamp-rejected" style="font-size: 0.6rem; padding: 2px 6px;">✕</span>
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email manzil</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="skeuo-input"
                        value="{{ old('email') }}"
                        placeholder="email@misol.uz"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Parol</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="skeuo-input"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="login-remember">
                    <label class="skeuo-checkbox">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Eslab qolish</span>
                    </label>
                </div>

                <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg w-full">
                    <i class="bi bi-key"></i> Kirish
                </button>
            </form>
        </div>
    </div>
</body>
</html>
