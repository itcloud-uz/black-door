<?php

declare(strict_types=1);

namespace App\Enums;

enum DebtDirection: string
{
    case TheyOweUs = 'they_owe_us';
    case WeOweThem = 'we_owe_them';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::TheyOweUs => 'Ular bizga qarzdor',
            self::WeOweThem => 'Biz ularga qarzdormiz',
        };
    }
}
