<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Black Door — PIN Tasdiqlash</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

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

        .pin-container {
            width: 100%;
            max-width: 400px;
        }

        .pin-safe {
            background: var(--surface);
            border: none;
            border-radius: var(--radius-xl);
            padding: var(--space-xl);
            box-shadow: var(--shadow-raised);
            position: relative;
        }

        /* Hide skeuomorphic elements */
        .pin-safe .rivet-tl,
        .pin-safe .rivet-tr,
        .pin-safe .rivet-bl,
        .pin-safe .rivet-br {
            display: none;
        }

        .pin-header {
            text-align: center;
            margin-bottom: var(--space-lg);
        }

        .safe-dial {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--surface);
            box-shadow: var(--shadow-raised);
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
        }
        .safe-dial::before {
            content: '🔒';
            font-size: 2.2rem;
        }
        .safe-dial div {
            display: none;
        }

        .pin-title {
            font-size: 0.95rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 800;
        }

        .pin-error {
            background: var(--surface);
            border: 2px solid var(--danger);
            border-radius: var(--radius-md);
            padding: var(--space-sm) var(--space-md);
            color: var(--danger);
            font-size: 0.8rem;
            text-align: center;
            margin-bottom: var(--space-md);
            box-shadow: var(--shadow-pressed-sm);
            font-weight: 600;
        }

        .pin-locked-overlay {
            position: absolute;
            inset: 0;
            background: rgba(238, 242, 247, 0.9);
            backdrop-filter: blur(4px);
            border-radius: var(--radius-xl);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .pin-locked-timer {
            font-family: var(--font-body);
            font-size: 2rem;
            font-weight: 800;
            color: var(--danger);
            margin-top: var(--space-md);
        }

        .pin-attempts {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: center;
            margin-top: var(--space-md);
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="pin-container" x-data="pinModal">

        <div class="pin-safe">
            {{-- Corner Rivets --}}
            <div class="rivet-tl"></div>
            <div class="rivet-tr"></div>
            <div class="rivet-bl"></div>
            <div class="rivet-br"></div>

            {{-- Lock Overlay --}}
            <template x-if="isLocked">
                <div class="pin-locked-overlay">
                    <span style="font-size: 3rem;">🔒</span>
                    <p style="color: var(--danger-red-light); margin-top: 12px;">Kirish bloklangan</p>
                    <div class="pin-locked-timer" x-text="lockTimeFormatted"></div>
                </div>
            </template>

            {{-- Header --}}
            <div class="pin-header">
                {{-- Safe Dial --}}
                <div class="safe-dial" style="margin-bottom: 16px;">
                    <div></div>
                </div>
                <p class="pin-title">PIN kodni kiriting</p>
            </div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="pin-error animate-shake">
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <template x-if="showError">
                <div class="pin-error animate-shake" x-text="errorMessage"></div>
            </template>

            {{-- PIN Display --}}
            <div class="pin-display">
                <template x-for="(dot, index) in pinDisplay" :key="index">
                    <div class="pin-dot" :class="{ 'filled': dot.filled }">
                        <span x-text="dot.filled ? '●' : ''"></span>
                    </div>
                </template>
            </div>

            {{-- Hidden Form --}}
            <form x-ref="pinForm" method="POST" action="{{ route('finance.pin.verify') }}" style="display: none;">
                @csrf
                <input type="hidden" name="pin" value="">
            </form>

            {{-- Keypad --}}
            <div class="pin-keypad">
                <template x-for="digit in [1,2,3,4,5,6,7,8,9]" :key="digit">
                    <button
                        class="pin-key"
                        @click="enterDigit(digit)"
                        :disabled="isLocked || isVerifying"
                        x-text="digit"
                    ></button>
                </template>

                <button class="pin-key pin-key-backspace" @click="deleteDigit()" :disabled="isLocked || isVerifying">
                    ⌫
                </button>

                <button class="pin-key" @click="enterDigit(0)" :disabled="isLocked || isVerifying">
                    0
                </button>

                <button class="pin-key pin-key-enter" @click="clearPin()" :disabled="isLocked || isVerifying">
                    ✕
                </button>
            </div>

            {{-- Attempts Counter --}}
            <div class="pin-attempts" x-show="failedAttempts > 0 && !isLocked">
                <span x-text="(maxAttempts - failedAttempts) + ' ta urinish qoldi'"></span>
            </div>
        </div>

    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pinModal', () => ({
                pin: ['','','',''],
                currentIndex: 0,
                isLocked: false,
                lockTimer: 0,
                failedAttempts: {{ session('failed_pin_attempts', 0) }},
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
        });
    </script>
</body>
</html>
