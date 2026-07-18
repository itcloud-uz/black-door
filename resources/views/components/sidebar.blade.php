{{-- Sidebar Component --}}
{{-- Dynamically shows menu items based on user role --}}
{{-- Finance items are NEVER shown to manager/employee roles --}}

@auth
@php
    $user = auth()->user();
    $role = $user->role->value;
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<aside class="sidebar" :class="{ 'open': open }">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <div class="door-knocker"></div>
        <h1>Black Door</h1>
        <div class="brand-subtitle">Moliyaviy Boshqaruv</div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- SUPER ADMIN Menu --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($role === 'super_admin')
        <div class="sidebar-section">
            <div class="sidebar-section-title">Boshqaruv</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}">
                        <span class="sidebar-icon">📊</span>
                        Bosh sahifa
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'admin.users') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}">
                        <span class="sidebar-icon">👥</span>
                        Foydalanuvchilar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'admin.objects') ? 'active' : '' }}">
                    <a href="{{ route('admin.objects.index') }}">
                        <span class="sidebar-icon">🏢</span>
                        Obyektlar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'admin.currency') ? 'active' : '' }}">
                    <a href="{{ route('admin.currency-rates') }}">
                        <span class="sidebar-icon">💱</span>
                        Valyuta kursi
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'admin.audit') ? 'active' : '' }}">
                    <a href="{{ route('admin.audit-log') }}">
                        <span class="sidebar-icon">📋</span>
                        Audit jurnal
                    </a>
                </li>
            </ul>
        </div>

        {{-- Admin Finance Access --}}
        <div class="sidebar-section">
            <div class="sidebar-section-title">🔒 Moliya (Qora Daftar)</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('finance.dashboard') }}">
                        <span class="sidebar-icon">📒</span>
                        Moliya sahifasi
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.cash-accounts') ? 'active' : '' }}">
                    <a href="{{ route('finance.cash-accounts.index') }}">
                        <span class="sidebar-icon">🏦</span>
                        Kassalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.transactions') ? 'active' : '' }}">
                    <a href="{{ route('finance.transactions.index') }}">
                        <span class="sidebar-icon">💰</span>
                        Tranzaksiyalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.counterparties') ? 'active' : '' }}">
                    <a href="{{ route('finance.counterparties.index') }}">
                        <span class="sidebar-icon">🤝</span>
                        Kontragentlar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.categories') ? 'active' : '' }}">
                    <a href="{{ route('finance.categories.index') }}">
                        <span class="sidebar-icon">📂</span>
                        Kategoriyalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.reports') ? 'active' : '' }}">
                    <a href="{{ route('finance.reports.index') }}">
                        <span class="sidebar-icon">📈</span>
                        Hisobotlar
                    </a>
                </li>
            </ul>
        </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- FINANCIER Menu --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($role === 'financier')
        <div class="sidebar-section">
            <div class="sidebar-section-title">🔒 Moliya moduli</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('finance.dashboard') }}">
                        <span class="sidebar-icon">📒</span>
                        Bosh sahifa
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.cash-accounts') ? 'active' : '' }}">
                    <a href="{{ route('finance.cash-accounts.index') }}">
                        <span class="sidebar-icon">🏦</span>
                        Kassalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.transactions') ? 'active' : '' }}">
                    <a href="{{ route('finance.transactions.index') }}">
                        <span class="sidebar-icon">💰</span>
                        Tranzaksiyalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.counterparties') ? 'active' : '' }}">
                    <a href="{{ route('finance.counterparties.index') }}">
                        <span class="sidebar-icon">🤝</span>
                        Kontragentlar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.categories') ? 'active' : '' }}">
                    <a href="{{ route('finance.categories.index') }}">
                        <span class="sidebar-icon">📂</span>
                        Kategoriyalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'finance.reports') ? 'active' : '' }}">
                    <a href="{{ route('finance.reports.index') }}">
                        <span class="sidebar-icon">📈</span>
                        Hisobotlar
                    </a>
                </li>
            </ul>
        </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- MANAGER Menu --}}
    {{-- CRITICAL: No finance items here --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($role === 'manager')
        <div class="sidebar-section">
            <div class="sidebar-section-title">Obyekt boshqaruvi</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'manager.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('manager.dashboard') }}">
                        <span class="sidebar-icon">📊</span>
                        Bosh sahifa
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'manager.employees') ? 'active' : '' }}">
                    <a href="{{ route('manager.employees.index') }}">
                        <span class="sidebar-icon">👷</span>
                        Xodimlar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'manager.transactions') ? 'active' : '' }}">
                    <a href="{{ route('manager.transactions.index') }}">
                        <span class="sidebar-icon">💰</span>
                        Tranzaksiyalar
                    </a>
                </li>
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'manager.warehouse') ? 'active' : '' }}">
                    <a href="{{ route('manager.warehouse.index') }}">
                        <span class="sidebar-icon">📦</span>
                        Ombor
                    </a>
                </li>
            </ul>
        </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- EMPLOYEE Menu --}}
    {{-- CRITICAL: No finance items here --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($role === 'employee')
        <div class="sidebar-section">
            <div class="sidebar-section-title">Mening sahifam</div>
            <ul class="sidebar-nav">
                <li class="sidebar-item {{ str_starts_with($currentRoute, 'manager.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('manager.dashboard') }}">
                        <span class="sidebar-icon">📊</span>
                        Bosh sahifa
                    </a>
                </li>
            </ul>
        </div>
    @endif

    {{-- Sidebar Footer --}}
    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <button type="submit">
                        <span class="sidebar-icon">🚪</span>
                        Chiqish
                    </button>
                </li>
            </ul>
        </form>
    </div>
</aside>
@endauth
