<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductUnit: string
{
    case Piece = 'piece';
    case Kg = 'kg';
    case Liter = 'liter';
    case Meter = 'meter';
    case SquareMeter = 'sqm';
    case CubicMeter = 'cbm';
    case Ton = 'ton';
    case Set = 'set';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Piece => 'dona',
            self::Kg => 'kg',
            self::Liter => 'litr',
            self::Meter => 'metr',
            self::SquareMeter => 'm²',
            self::CubicMeter => 'm³',
            self::Ton => 'tonna',
            self::Set => 'to\'plam',
        };
    }

    /**
     * Qisqartmasi.
     */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Piece => 'dona',
            self::Kg => 'kg',
            self::Liter => 'l',
            self::Meter => 'm',
            self::SquareMeter => 'm²',
            self::CubicMeter => 'm³',
            self::Ton => 't',
            self::Set => 'to\'plam',
        };
    }
}
