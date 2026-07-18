<?php

declare(strict_types=1);

namespace App\Enums;

enum WarehouseMovementType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Transfer = 'transfer';
    case InventoryAdjustment = 'inventory_adjustment';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Incoming => 'Kirim',
            self::Outgoing => 'Chiqim',
            self::Transfer => 'O\'tkazma',
            self::InventoryAdjustment => 'Inventarizatsiya tuzatish',
        };
    }

    /**
     * Ombor zahirasiga ta'sir yo'nalishi: +1 = kirim, -1 = chiqim.
     */
    public function stockDirection(): int
    {
        return match ($this) {
            self::Incoming => 1,
            self::Outgoing => -1,
            self::Transfer => 0, // alohida logika bilan boshqariladi
            self::InventoryAdjustment => 0, // farqga qarab belgilanadi
        };
    }
}
