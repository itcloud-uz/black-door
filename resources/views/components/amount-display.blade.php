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
])

@php
    $amountInt = (int) $amount;
    $isPositive = $amountInt >= 0;
    $absAmount = abs($amountInt);
    $main = intdiv($absAmount, 100);
    $sub = $absAmount % 100;
    $sign = $amountInt < 0 ? '-' : ($amountInt > 0 ? '+' : '');

    $colorClass = $isPositive ? 'amount-positive' : 'amount-negative';
    if ($amountInt === 0) $colorClass = '';

    $sizeClass = match($size) {
        'sm' => 'text-sm',
        'lg' => 'amount-lg',
        default => '',
    };

    $fontClass = $style === 'handwriting' ? 'amount-handwriting' : 'mono-number';

    if ($currency === 'USD') {
        $formatted = $sign . '$' . number_format($main) . '.' . str_pad((string)$sub, 2, '0', STR_PAD_LEFT);
    } else {
        $formatted = $sign . number_format($main, 0, '.', ' ') . '.' . str_pad((string)$sub, 2, '0', STR_PAD_LEFT) . ' сўм';
    }
@endphp

<span class="amount {{ $fontClass }} {{ $colorClass }} {{ $sizeClass }}" {{ $attributes }}>
    {{ $formatted }}
</span>
