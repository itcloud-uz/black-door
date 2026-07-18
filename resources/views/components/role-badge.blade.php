{{--
    Role Badge Component
    Usage: <x-role-badge :role="$user->role" />
    Admin = gold, Financier = copper, Manager = steel, Employee = bronze
--}}

@props(['role'])

@php
    $roleValue = is_string($role) ? $role : $role->value;

    $config = match($roleValue) {
        'super_admin' => ['class' => 'skeuo-badge-gold', 'label' => 'Admin'],
        'financier'   => ['class' => 'skeuo-badge-copper', 'label' => 'Moliyachi'],
        'manager'     => ['class' => 'skeuo-badge-steel', 'label' => 'Menejer'],
        'employee'    => ['class' => 'skeuo-badge-bronze', 'label' => 'Xodim'],
        default       => ['class' => 'skeuo-badge-steel', 'label' => $roleValue],
    };
@endphp

<span class="skeuo-badge {{ $config['class'] }}" {{ $attributes }}>
    {{ $config['label'] }}
</span>
