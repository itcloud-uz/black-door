<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Black Door — Litsenziya olish uchun ariza topshirish</title>

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

        .portal-container {
            width: 100%;
            max-width: 600px;
        }

        .portal-card {
            background: var(--surface);
            border: none;
            border-radius: var(--radius-xl);
            padding: var(--space-2xl) var(--space-xl);
            box-shadow: var(--shadow-raised);
        }

        .portal-header {
            text-align: center;
            margin-bottom: var(--space-xl);
        }

        .divider {
            height: 2px;
            background: var(--shadow-dark);
            opacity: 0.4;
            margin: var(--space-md) 0;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-card">
            {{-- Header --}}
            <div class="portal-header" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <img src="{{ asset('branding/logo_vertical.png') }}" alt="Black Door" style="max-width: 180px; height: auto;">
                <h2 style="font-size: 1.25rem; font-weight: 800; text-transform: uppercase; color: var(--text-primary); margin-top: 16px;">Litsenziya olish uchun ariza</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">Biznesingizni avtomatlashtirish va ishonchli nazorat ostiga olish vaqti keldi.</p>
            </div>

            <div class="divider"></div>

            @if(session('success'))
                <div class="skeuo-alert skeuo-alert-success mb-md">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="skeuo-alert skeuo-alert-danger mb-md">
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            {{-- Request Form --}}
            <form method="POST" action="{{ route('control.portal.request.submit') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="company_name">Kompaniya nomi (Yuridik shaxs yoki MChJ):</label>
                    <input type="text" id="company_name" name="company_name" class="skeuo-input" placeholder="Masalan: Grand Agro MChJ" required value="{{ old('company_name') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact_name">Mas'ul shaxs (Ism familiyasi):</label>
                    <input type="text" id="contact_name" name="contact_name" class="skeuo-input" placeholder="Masalan: Sardor Alimov" required value="{{ old('contact_name') }}">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefon raqam:</label>
                        <input type="text" id="phone" name="phone" class="skeuo-input" placeholder="+998 90 123 45 67" required value="{{ old('phone') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail manzil:</label>
                        <input type="email" id="email" name="email" class="skeuo-input" placeholder="info@kompaniya.uz" required value="{{ old('email') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="product_tariff">Tanlamoqchi bo'lgan mahsulot va tarifingiz:</label>
                    <select id="product_tariff" name="tariff_plan_id" class="skeuo-input" required onchange="updateProductField()">
                        @foreach($products as $prod)
                            <optgroup label="{{ $prod->name }}">
                                @foreach($prod->tariffPlans as $plan)
                                    <option value="{{ $plan->id }}" data-product="{{ $prod->id }}" {{ old('tariff_plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} ({{ strtoupper($plan->code) }}) — {{ $plan->currency === 'USD' ? '$' : '' }}{{ number_format($plan->price / 100, 2, '.', ' ') }}{{ $plan->currency === 'UZS' ? ' UZS' : '' }} / {{ $plan->duration_days ? $plan->duration_days . ' kun' : 'Muddatsiz' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Hidden product field --}}
                <input type="hidden" name="product_id" id="hidden_product_id" value="">

                <div class="form-group">
                    <label class="form-label" for="notes">Maxsus istak yoki eslatmalar:</label>
                    <textarea id="notes" name="notes" class="skeuo-input" style="height: 80px;" placeholder="Qo'shimcha savollaringiz bo'lsa yozib qoldiring...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="skeuo-btn skeuo-btn-primary skeuo-btn-lg w-full" style="font-weight: bold; margin-top: 10px;">
                    <i class="bi bi-send"></i> Arizani yuborish
                </button>
            </form>

            <div class="divider"></div>

            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                Tizim faol mijozimisiz? Unda tizimingizning <a href="/license/activate" style="color: var(--text-primary); text-decoration: underline;">faollashtirish sahifasiga</a> o'ting.
            </div>
        </div>
    </div>

    <script>
        function updateProductField() {
            var select = document.getElementById('product_tariff');
            if (select.selectedIndex !== -1) {
                var selectedOption = select.options[select.selectedIndex];
                var productId = selectedOption.getAttribute('data-product');
                document.getElementById('hidden_product_id').value = productId;
            }
        }
        // Run once on load
        window.addEventListener('DOMContentLoaded', updateProductField);
    </script>
</body>
</html>
