<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ \App\Models\Setting::get('company_name', 'Black Door') }} — Korxona moliyaviy boshqaruv tizimi">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::get('company_name', 'Black Door'))</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap Icons CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- App CSS --}}
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

    @stack('styles')
</head>
<body x-data="sidebarToggle">

    {{-- Mobile Sidebar Overlay --}}
    <div class="sidebar-overlay"
         :class="{ 'show': open }"
         @click="open = false"></div>

    <div class="app-layout">

        {{-- Sidebar --}}
        @include('components.sidebar')

        {{-- Main Content Wrapper --}}
        <div class="main-wrapper">

            {{-- Top Bar --}}
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="mobile-menu-btn" @click="open = !open">
                        <span x-show="!open"><i class="bi bi-list"></i></span>
                        <span x-show="open"><i class="bi bi-x-lg"></i></span>
                    </button>

                    @hasSection('breadcrumb')
                        <nav>
                            <ul class="breadcrumb">
                                @yield('breadcrumb')
                            </ul>
                        </nav>
                    @endif
                </div>

                <div class="top-bar-right">
                    @auth
                        <div class="top-bar-user">
                            <span>{{ auth()->user()->name }}</span>
                            <x-role-badge :role="auth()->user()->role" />
                        </div>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="skeuo-btn skeuo-btn-sm" title="Chiqish">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            {{-- Main Content --}}
            <main class="main-content">
                {{-- Flash Messages --}}
                <x-flash-message />

                @yield('content')
            </main>

        </div>
    </div>

    {{-- Alpine.js via CDN --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Inline Alpine Data (used by CDN approach) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            /* Sidebar Toggle */
            Alpine.data('sidebarToggle', () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                    document.body.style.overflow = this.open ? 'hidden' : '';
                },
                close() {
                    this.open = false;
                    document.body.style.overflow = '';
                },
                init() {
                    window.addEventListener('resize', () => {
                        if (window.innerWidth > 768) this.close();
                    });
                }
            }));

            /* Notification System */
            Alpine.data('notification', () => ({
                notifications: [],
                nextId: 0,
                add(message, type = 'success', duration = 5000) {
                    const id = this.nextId++;
                    this.notifications.push({ id, message, type, visible: true });
                    if (duration > 0) setTimeout(() => this.remove(id), duration);
                },
                remove(id) {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                },
                success(msg) { this.add(msg, 'success'); },
                error(msg) { this.add(msg, 'error', 8000); },
                warning(msg) { this.add(msg, 'warning', 6000); }
            }));

            /* Confirm Dialog */
            Alpine.data('confirmDialog', () => ({
                show: false,
                title: '',
                message: '',
                confirmText: "Tasdiqlash",
                cancelText: "Bekor qilish",
                formAction: null,
                isDangerous: false,
                open(opts = {}) {
                    this.title = opts.title || 'Tasdiqlash';
                    this.message = opts.message || 'Ishonchingiz komilmi?';
                    this.confirmText = opts.confirmText || 'Tasdiqlash';
                    this.isDangerous = opts.isDangerous || false;
                    this.formAction = opts.formAction || null;
                    this.show = true;
                },
                confirm() {
                    if (this.formAction) {
                        const form = document.querySelector(this.formAction);
                        if (form) form.submit();
                    }
                    this.close();
                },
                close() { this.show = false; this.formAction = null; }
            }));

            /* Amount Formatter */
            Alpine.data('amountFormatter', () => ({
                formatAmount(subunits, currency = 'USD') {
                    const main = Math.floor(Math.abs(subunits) / 100);
                    const sub = Math.abs(subunits) % 100;
                    const sign = subunits < 0 ? '-' : '';
                    const mainFormatted = main.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                    const subFormatted = sub.toString().padStart(2, '0');
                    if (currency === 'USD') return sign + '$' + mainFormatted + '.' + subFormatted;
                    return sign + mainFormatted + '.' + subFormatted + ' so\'m';
                },
                isPositive(amount) { return amount >= 0; }
            }));

            /* Table Filter */
            Alpine.data('tableFilter', () => ({
                searchQuery: '',
                filterRows() {
                    const query = this.searchQuery.toLowerCase().trim();
                    const rows = this.$refs.tableBody?.querySelectorAll('tr') || [];
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = (query === '' || text.includes(query)) ? '' : 'none';
                    });
                }
            }));

            /* Dynamic Form (for user creation) */
            Alpine.data('dynamicForm', () => ({
                selectedRole: '',
                selectedType: '',
                get showObjectSelector() { return this.selectedRole === 'manager'; },
                get showPinField() { return this.selectedRole === 'financier'; },
                get objectTypeIcon() {
                    return { factory: '<i class="bi bi-building-gear"></i>', construction: '<i class="bi bi-cone-striped"></i>', warehouse: '<i class="bi bi-shop"></i>' }[this.selectedType] || '<i class="bi bi-building"></i>';
                }
            }));

            /* Currency Calculator */
            Alpine.data('currencyCalculator', () => ({
                amount: '',
                fromCurrency: 'USD',
                toCurrency: 'UZS',
                rate: parseFloat(document.querySelector('[data-exchange-rate]')?.dataset?.exchangeRate || '12500'),
                result: 0,
                calculate() {
                    const val = parseFloat(this.amount) || 0;
                    if (this.fromCurrency === 'USD' && this.toCurrency === 'UZS') this.result = val * this.rate;
                    else if (this.fromCurrency === 'UZS' && this.toCurrency === 'USD') this.result = this.rate > 0 ? val / this.rate : 0;
                    else this.result = val;
                },
                swap() {
                    [this.fromCurrency, this.toCurrency] = [this.toCurrency, this.fromCurrency];
                    this.calculate();
                },
                get formattedResult() {
                    const opts = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                    return this.toCurrency === 'UZS'
                        ? new Intl.NumberFormat('uz-UZ', opts).format(this.result)
                        : new Intl.NumberFormat('en-US', opts).format(this.result);
                }
            }));

            /* PIN Modal */
            Alpine.data('pinModal', () => ({
                pin: ['','','',''],
                currentIndex: 0,
                isLocked: false,
                lockTimer: 0,
                failedAttempts: 0,
                maxAttempts: 5,
                isVerifying: false,
                showError: false,
                errorMessage: '',
                enterDigit(d) {
                    if (this.isLocked || this.isVerifying || this.currentIndex >= 4) return;
                    this.showError = false;
                    this.pin[this.currentIndex] = d.toString();
                    this.currentIndex++;
                    if (this.currentIndex === 4) this.submitPin();
                },
                deleteDigit() {
                    if (this.isLocked || this.isVerifying || this.currentIndex <= 0) return;
                    this.currentIndex--;
                    this.pin[this.currentIndex] = '';
                },
                clearPin() { this.pin = ['','','','']; this.currentIndex = 0; this.showError = false; },
                submitPin() {
                    this.isVerifying = true;
                    const form = this.$refs.pinForm;
                    if (form) {
                        form.querySelector('input[name="pin"]').value = this.pin.join('');
                        form.submit();
                    }
                },
                get lockTimeFormatted() {
                    return Math.floor(this.lockTimer / 60) + ':' + (this.lockTimer % 60).toString().padStart(2, '0');
                },
                get pinDisplay() {
                    return this.pin.map((d, i) => ({ value: d, filled: d !== '', active: i === this.currentIndex }));
                }
            }));

            /* Warehouse Manager */
            Alpine.data('warehouseManager', () => ({
                showIncoming: false,
                showOutgoing: false,
                toggleIncoming() { this.showIncoming = !this.showIncoming; this.showOutgoing = false; },
                toggleOutgoing() { this.showOutgoing = !this.showOutgoing; this.showIncoming = false; }
            }));
        });
    </script>

    @stack('scripts')
</body>
</html>
