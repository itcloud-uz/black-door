{{--
    Amount Display Component
    Usage: <x-amount-display :amount="$amount" currency="USD" />
    Props:
      - $amount (int) — amount in subunits (cents/tiyin)
      - $currency (string) — 'USD' or 'UZS'
      - $size (string, optional) — 'sm', 'md', 'lg'
      - $style (string, optional) — 'default', 'handwriting'
--}}

@props([
    'amount' => 0,
    'currency' => 'USD',
    'size' => 'md',
    'style' => 'default',
    'showPlus' => false,
    'scale' => false,
])

@php
    $amountInt = (int) $amount;
    $isPositive = $amountInt >= 0;
    $absAmount = abs($amountInt);
    $main = intdiv($absAmount, 100);
    $sub = $absAmount % 100;

    // Sign logic
    $sign = '';
    if ($amountInt < 0) {
        $sign = '-';
    } elseif ($amountInt > 0 && $showPlus) {
        $sign = '+';
    }

    $colorClass = $isPositive ? 'amount-positive' : 'amount-negative';
    if ($amountInt === 0) {
        $colorClass = 'amount-neutral';
    }

    $sizeClass = match($size) {
        'sm' => 'text-sm',
        'lg' => 'amount-lg',
        default => '',
    };

    $fontClass = $style === 'handwriting' ? 'amount-handwriting' : 'mono-number';

    // Full formatted string for tooltips
    if ($currency === 'USD') {
        $fullFormatted = $sign . '$' . number_format($main, 0, '.', ' ') . '.' . str_pad((string)$sub, 2, '0', STR_PAD_LEFT);
    } else {
        $fullFormatted = $sign . number_format($main, 0, '.', ' ') . '.' . str_pad((string)$sub, 2, '0', STR_PAD_LEFT) . ' so\'m';
    }

    // Abbreviated formatted string for extreme amounts (>= 1 billion)
    if ($main >= 1000000000) {
        $billions = $main / 1000000000;
        $formattedMain = number_format($billions, 2, '.', ' ');
        if ($currency === 'USD') {
            $formatted = $sign . '$' . $formattedMain . ' mlrd';
        } else {
            $formatted = $sign . $formattedMain . ' mlrd so\'m';
        }
    } else {
        $formatted = $fullFormatted;
    }
@endphp

@if($scale)
    <div x-data="{
        fs: null,
        adjust() {
            this.$nextTick(() => {
                const parent = this.$el;
                const text = this.$refs.amountText;
                if (!parent || !text) return;
                text.style.fontSize = ''; // Reset
                let currentFs = parseFloat(window.getComputedStyle(text).fontSize);
                const maxAttempts = 15;
                let attempt = 0;
                while (text.scrollWidth > parent.clientWidth && currentFs > 9 && attempt < maxAttempts) {
                    currentFs -= 1;
                    text.style.fontSize = currentFs + 'px';
                    attempt++;
                }
            });
        }
    }" x-init="adjust()" @resize.window="adjust()" class="amount-scale-container" style="overflow: hidden; width: 100%; display: flex; justify-content: inherit; align-items: center; min-width: 0;">
        <span x-ref="amountText" class="amount {{ $fontClass }} {{ $colorClass }} {{ $sizeClass }}" style="white-space: nowrap; transition: font-size 0.05s ease;" title="{{ $fullFormatted }}" {{ $attributes }}>
            {{ $formatted }}
        </span>
    </div>
@else
    <span class="amount {{ $fontClass }} {{ $colorClass }} {{ $sizeClass }}" style="white-space: nowrap;" title="{{ $fullFormatted }}" {{ $attributes }}>
        {{ $formatted }}
    </span>
@endif
