import Alpine from 'alpinejs';
window.Alpine = Alpine;

/* ================================================================
   BLACK DOOR — Alpine.js Components
   Enterprise Financial Management System
   ================================================================ */

/**
 * PIN Code Modal — 4-digit PIN entry with lockout
 */
Alpine.data('pinModal', () => ({
    pin: ['', '', '', ''],
    currentIndex: 0,
    isLocked: false,
    lockTimer: 0,
    failedAttempts: 0,
    maxAttempts: 5,
    lockDuration: 300,
    isVerifying: false,
    showError: false,
    errorMessage: '',

    init() {
        this.$watch('lockTimer', (value) => {
            if (value <= 0) {
                this.isLocked = false;
            }
        });
    },

    enterDigit(digit) {
        if (this.isLocked || this.isVerifying) return;
        if (this.currentIndex >= 4) return;

        this.showError = false;
        this.pin[this.currentIndex] = digit.toString();
        this.currentIndex++;

        if (this.currentIndex === 4) {
            this.submitPin();
        }
    },

    deleteDigit() {
        if (this.isLocked || this.isVerifying) return;
        if (this.currentIndex <= 0) return;

        this.currentIndex--;
        this.pin[this.currentIndex] = '';
    },

    clearPin() {
        this.pin = ['', '', '', ''];
        this.currentIndex = 0;
        this.showError = false;
    },

    async submitPin() {
        this.isVerifying = true;
        const pinCode = this.pin.join('');

        try {
            const form = this.$refs.pinForm;
            if (form) {
                const input = form.querySelector('input[name="pin"]');
                if (input) {
                    input.value = pinCode;
                    form.submit();
                    return;
                }
            }
        } catch (e) {
            this.handleFailure("Xatolik yuz berdi");
        }
    },

    handleFailure(message) {
        this.isVerifying = false;
        this.failedAttempts++;
        this.showError = true;
        this.errorMessage = message || `Noto'g'ri PIN. ${this.maxAttempts - this.failedAttempts} ta urinish qoldi.`;
        this.clearPin();

        if (this.failedAttempts >= this.maxAttempts) {
            this.lockOut();
        }
    },

    lockOut() {
        this.isLocked = true;
        this.lockTimer = this.lockDuration;

        const interval = setInterval(() => {
            this.lockTimer--;
            if (this.lockTimer <= 0) {
                clearInterval(interval);
                this.isLocked = false;
                this.failedAttempts = 0;
            }
        }, 1000);
    },

    get lockTimeFormatted() {
        const min = Math.floor(this.lockTimer / 60);
        const sec = this.lockTimer % 60;
        return `${min}:${sec.toString().padStart(2, '0')}`;
    },

    get pinDisplay() {
        return this.pin.map((d, i) => ({
            value: d,
            filled: d !== '',
            active: i === this.currentIndex
        }));
    }
}));

/**
 * Currency Calculator — live USD ↔ UZS conversion
 */
Alpine.data('currencyCalculator', () => ({
    amount: '',
    fromCurrency: 'USD',
    toCurrency: 'UZS',
    rate: 0,
    result: 0,

    init() {
        const rateEl = document.querySelector('[data-exchange-rate]');
        if (rateEl) {
            this.rate = parseFloat(rateEl.dataset.exchangeRate) || 12500;
        }
    },

    calculate() {
        const val = parseFloat(this.amount) || 0;
        if (this.fromCurrency === 'USD' && this.toCurrency === 'UZS') {
            this.result = val * this.rate;
        } else if (this.fromCurrency === 'UZS' && this.toCurrency === 'USD') {
            this.result = this.rate > 0 ? val / this.rate : 0;
        } else {
            this.result = val;
        }
    },

    swap() {
        [this.fromCurrency, this.toCurrency] = [this.toCurrency, this.fromCurrency];
        this.calculate();
    },

    get formattedResult() {
        if (this.toCurrency === 'UZS') {
            return new Intl.NumberFormat('uz-UZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(this.result);
        }
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(this.result);
    }
}));

/**
 * Transaction Form — dynamic form with amount validation
 */
Alpine.data('transactionForm', () => ({
    type: 'income',
    amount: '',
    currency: 'USD',
    cashAccountId: '',
    counterpartyId: '',
    categoryId: '',
    description: '',
    isSubmitting: false,

    init() {
        this.$watch('amount', () => this.validateAmount());
    },

    validateAmount() {
        const val = parseFloat(this.amount);
        if (isNaN(val) || val < 0) {
            this.amount = '';
        }
    },

    get amountInSubunits() {
        const val = parseFloat(this.amount) || 0;
        return Math.round(val * 100);
    },

    get formattedAmount() {
        const val = parseFloat(this.amount) || 0;
        if (this.currency === 'USD') {
            return '$' + new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(val);
        }
        return new Intl.NumberFormat('uz-UZ', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(val) + ' сўм';
    },

    get isValid() {
        return parseFloat(this.amount) > 0 && this.cashAccountId && this.categoryId;
    },

    submit() {
        if (!this.isValid || this.isSubmitting) return;
        this.isSubmitting = true;
        this.$refs.form?.submit();
    }
}));

/**
 * Sidebar Toggle — mobile sidebar control
 */
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
            if (window.innerWidth > 768) {
                this.close();
            }
        });
    }
}));

/**
 * Notification — toast notification system
 */
Alpine.data('notification', () => ({
    notifications: [],
    nextId: 0,

    add(message, type = 'success', duration = 5000) {
        const id = this.nextId++;
        this.notifications.push({ id, message, type, visible: true });

        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }
    },

    remove(id) {
        const idx = this.notifications.findIndex(n => n.id === id);
        if (idx !== -1) {
            this.notifications[idx].visible = false;
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 300);
        }
    },

    success(message) { this.add(message, 'success'); },
    error(message) { this.add(message, 'error', 8000); },
    warning(message) { this.add(message, 'warning', 6000); }
}));

/**
 * Confirm Dialog — skeuomorphic confirmation dialog
 */
Alpine.data('confirmDialog', () => ({
    show: false,
    title: '',
    message: '',
    confirmText: 'Tasdiqlash',
    cancelText: 'Bekor qilish',
    confirmAction: null,
    isDangerous: false,

    open(options = {}) {
        this.title = options.title || 'Tasdiqlash';
        this.message = options.message || 'Ishonchingiz komilmi?';
        this.confirmText = options.confirmText || 'Tasdiqlash';
        this.cancelText = options.cancelText || 'Bekor qilish';
        this.isDangerous = options.isDangerous || false;
        this.confirmAction = options.onConfirm || null;
        this.show = true;
    },

    confirm() {
        if (this.confirmAction) {
            this.confirmAction();
        }
        this.close();
    },

    close() {
        this.show = false;
        this.confirmAction = null;
    }
}));

/**
 * Table Filter — dynamic table filtering & sorting
 */
Alpine.data('tableFilter', () => ({
    searchQuery: '',
    sortColumn: '',
    sortDirection: 'asc',

    filterRows() {
        const query = this.searchQuery.toLowerCase().trim();
        const rows = this.$refs.tableBody?.querySelectorAll('tr') || [];

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = query === '' || text.includes(query) ? '' : 'none';
        });
    },

    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
    },

    getSortIcon(column) {
        if (this.sortColumn !== column) return '⇅';
        return this.sortDirection === 'asc' ? '↑' : '↓';
    }
}));

/**
 * Amount Formatter — formats integer subunit amounts to currency display
 */
Alpine.data('amountFormatter', () => ({
    formatAmount(amountInSubunits, currency = 'USD') {
        const main = Math.floor(Math.abs(amountInSubunits) / 100);
        const sub = Math.abs(amountInSubunits) % 100;
        const sign = amountInSubunits < 0 ? '-' : '';
        const mainFormatted = main.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        const subFormatted = sub.toString().padStart(2, '0');

        if (currency === 'USD') {
            return sign + '$' + mainFormatted + '.' + subFormatted;
        }
        return sign + mainFormatted + '.' + subFormatted + ' so\'m';
    },

    formatNumber(value) {
        return new Intl.NumberFormat('uz-UZ').format(value);
    },

    isPositive(amount) {
        return amount >= 0;
    }
}));

/**
 * Dynamic Form Handler — handles conditional form fields
 */
Alpine.data('dynamicForm', () => ({
    selectedRole: '',
    selectedType: '',

    get showObjectSelector() {
        return this.selectedRole === 'manager';
    },

    get showPinField() {
        return this.selectedRole === 'financier';
    },

    get objectTypeIcon() {
        switch (this.selectedType) {
            case 'factory': return '🏭';
            case 'construction': return '🏗️';
            case 'warehouse': return '🏪';
            default: return '🏢';
        }
    }
}));

/**
 * Warehouse Manager — handles stock forms
 */
Alpine.data('warehouseManager', () => ({
    showIncoming: false,
    showOutgoing: false,
    products: [],
    selectedProduct: '',
    quantity: '',

    toggleIncoming() {
        this.showIncoming = !this.showIncoming;
        this.showOutgoing = false;
    },

    toggleOutgoing() {
        this.showOutgoing = !this.showOutgoing;
        this.showIncoming = false;
    },

    get isValid() {
        return this.selectedProduct && parseFloat(this.quantity) > 0;
    }
}));

/* ── Start Alpine ─────────────────────────────────────────────── */
Alpine.start();
