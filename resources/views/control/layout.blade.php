<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Black Door Control')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ \App\Models\Setting::get('theme_css_version', '1') }}">
    <style>
        .control-layout {
            display: flex;
            min-height: 100vh;
        }
        .control-sidebar {
            width: 260px;
            background: var(--surface);
            box-shadow: var(--shadow-raised);
            padding: var(--space-lg);
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .control-main {
            flex: 1;
            padding: var(--space-xl);
            background: var(--bg-color);
            overflow-y: auto;
        }
        .sidebar-brand-ctrl {
            text-align: center;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding-bottom: var(--space-md);
        }
        .sidebar-brand-ctrl h1 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 8px 0 0 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .sidebar-menu-ctrl {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .sidebar-menu-ctrl a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            color: var(--text-muted);
            font-weight: 700;
            transition: all 0.2s;
            text-decoration: none;
        }
        .sidebar-menu-ctrl li.active a, .sidebar-menu-ctrl a:hover {
            background: var(--surface);
            color: var(--text-primary);
            box-shadow: var(--shadow-neutral-sm);
        }
    </style>
</head>
<body>
    <div class="control-layout">
        <aside class="control-sidebar">
            <div class="sidebar-brand-ctrl">
                <img src="{{ asset('branding/mark.png') }}" alt="Control Logo" style="width: 60px; height: 60px; display: block; margin: 0 auto;">
                <h1>BD Control</h1>
                <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 800; letter-spacing: 0.5px;">SOTUV & LITSENZIYA</span>
            </div>
            
            <ul class="sidebar-menu-ctrl">
                <li class="{{ request()->routeIs('control.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('control.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="{{ request()->routeIs('control.products.*') ? 'active' : '' }}">
                    <a href="{{ route('control.products.index') }}">
                        <i class="bi bi-box-seam"></i> Katalog
                    </a>
                </li>
                <li class="{{ request()->routeIs('control.clients.*') ? 'active' : '' }}">
                    <a href="{{ route('control.clients.index') }}">
                        <i class="bi bi-people"></i> Mijozlar
                    </a>
                </li>
                <li class="{{ request()->routeIs('control.requests.index') ? 'active' : '' }}">
                    <a href="{{ route('control.requests.index') }}">
                        <i class="bi bi-file-earmark-text"></i> Arizalar
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-arrow-left-circle"></i> Asosiy Tizim
                    </a>
                </li>
            </ul>

            <div style="margin-top: auto;">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="skeuo-btn text-red w-full" style="border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; padding: 12px;">
                        <i class="bi bi-box-arrow-right"></i> Chiqish
                    </button>
                </form>
            </div>
        </aside>
        
        <main class="control-main">
            @yield('content')
        </main>
    </div>
</body>
</html>
