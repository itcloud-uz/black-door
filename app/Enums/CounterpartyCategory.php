<?php

declare(strict_types=1);

namespace App\Enums;

enum CounterpartyCategory: string
{
    case Client = 'client';
    case Supplier = 'supplier';
    case Partner = 'partner';
    case Other = 'other';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Client => 'Mijoz',
            self::Supplier => 'Yetkazib beruvchi',
            self::Partner => 'Hamkor',
            self::Other => 'Boshqa',
        };
    }
}
