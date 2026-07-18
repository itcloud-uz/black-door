<?php

declare(strict_types=1);

namespace App\Enums;

enum ObjectType: string
{
    case Factory = 'factory';
    case Construction = 'construction';
    case Warehouse = 'warehouse';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Factory => 'Zavod',
            self::Construction => 'Qurilish',
            self::Warehouse => 'Ombor',
        };
    }
}
