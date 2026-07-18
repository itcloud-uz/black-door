<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Financier = 'financier';
    case Manager = 'manager';
    case Employee = 'employee';

    /**
     * Rolni o'zbekcha nomlash.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Financier => 'Moliyachi',
            self::Manager => 'Menejer',
            self::Employee => 'Xodim',
        };
    }

    /**
     * Moliya moduliga kirish huquqi bormi.
     */
    public function canAccessFinance(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Financier => true,
            default => false,
        };
    }
}
