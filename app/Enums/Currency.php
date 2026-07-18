<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case UZS = 'UZS';

    /**
     * Valyuta belgisi.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::UZS => 'сўм',
        };
    }

    /**
     * Kichik birlik nomi.
     */
    public function subunitName(): string
    {
        return match ($this) {
            self::USD => 'sent',
            self::UZS => 'tiyin',
        };
    }

    /**
     * Kichik birlik ko'paytiruvchisi (1 asosiy = 100 kichik).
     */
    public function subunitMultiplier(): int
    {
        return 100;
    }

    /**
     * Tiyindan / sentdan formatlash.
     */
    public function format(int $amountInSubunits): string
    {
        $main = intdiv($amountInSubunits, $this->subunitMultiplier());
        $sub = abs($amountInSubunits % $this->subunitMultiplier());

        return match ($this) {
            self::USD => sprintf('$%s.%02d', number_format($main), $sub),
            self::UZS => sprintf('%s.%02d сўм', number_format($main, 0, '.', ' '), $sub),
        };
    }
}
