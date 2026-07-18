{{--
    Currency Badge Component
    Usage: <x-currency-badge currency="USD" />
--}}

@props(['currency' => 'USD'])

@php
    $badgeClass = $currency === 'USD' ? 'skeuo-badge-usd' : 'skeuo-badge-uzs';
    $label = $currency === 'USD' ? '$ USD' : 'сўм UZS';
@endphp

<span class="skeuo-badge {{ $badgeClass }}" {{ $attributes }}>
    {{ $label }}
</span>
