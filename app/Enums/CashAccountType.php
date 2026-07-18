<?php

declare(strict_types=1);

namespace App\Enums;

enum CashAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Card = 'card';
    case Safe = 'safe';
    case Other = 'other';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Naqd pul',
            self::Bank => 'Bank hisob',
            self::Card => 'Karta',
            self::Safe => 'Seyf',
            self::Other => 'Boshqa',
        };
    }
}
