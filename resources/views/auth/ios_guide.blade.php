<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Black Door — iOS O'rnatish Qo'llanmasi</title>

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

        .guide-container {
            width: 100%;
            max-width: 440px;
        }

        .guide-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: var(--space-xl);
            box-shadow: var(--shadow-raised);
            position: relative;
        }

        .step-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
            margin-bottom: 24px;
        }

        .step-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: var(--bg-color);
            padding: var(--space-md);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-pressed-sm);
        }

        .step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--color-primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            flex-shrink: 0;
            box-shadow: var(--shadow-neutral-sm);
        }

        .step-text {
            font-size: 0.9rem;
            color: var(--text-primary);
            line-height: 1.4;
        }
    </style>
</head>
<body>

<div class="guide-container">
    <div class="guide-card text-center">
        
        <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--surface); box-shadow: var(--shadow-neutral-sm); display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <i class="bi bi-apple text-dark" style="font-size: 1.8rem;"></i>
            </div>
            <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">iOS O'rnatish</h2>
            <p class="text-muted" style="font-size: 0.85rem; margin-top: 4px;">iPhone va iPad uchun qo'llanma</p>
        </div>

        <div class="step-list">
            <div class="step-item">
                <span class="step-num">1</span>
                <span class="step-text">
                    <strong>Safari</strong> brauzerini oching va tizim manzili (blackdoor.uz) ga kiring.
                </span>
            </div>
            <div class="step-item">
                <span class="step-num">2</span>
                <span class="step-text">
                    Ekran pastidagi <strong>Share (Ulashish)</strong> <i class="bi bi-box-arrow-up text-primary"></i> tugmasini bosing.
                </span>
            </div>
            <div class="step-item">
                <span class="step-num">3</span>
                <span class="step-text">
                    Ochilgan ro'yxatdan <strong>Add to Home Screen (Ekraningizga qo'shish)</strong> <i class="bi bi-plus-square text-primary"></i> variantini tanlang.
                </span>
            </div>
            <div class="step-item">
                <span class="step-num">4</span>
                <span class="step-text">
                    Oynada <strong>Add (Qo'shish)</strong> tugmasini tanlang. Ilova telefoningiz ekranida mustaqil ishlaydigan PWA shaklida o'rnatiladi.
                </span>
            </div>
        </div>

        <a href="{{ route('login') }}" class="skeuo-btn w-full" style="text-decoration: none; padding: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600;">
            <i class="bi bi-arrow-left"></i> Login sahifasiga qaytish
        </a>

    </div>
</div>

</body>
</html>
