<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Exchange = 'exchange';

    /**
     * O'zbekcha nomi.
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => 'Kirim',
            self::Expense => 'Chiqim',
            self::TransferOut => 'O\'tkazma (chiqish)',
            self::TransferIn => 'O\'tkazma (kirish)',
            self::Exchange => 'Valyuta ayirboshlash',
        };
    }

    /**
     * Balansga ta'sir yo'nalishi: +1 = kirim, -1 = chiqim.
     */
    public function balanceDirection(): int
    {
        return match ($this) {
            self::Income, self::TransferIn => 1,
            self::Expense, self::TransferOut => -1,
            self::Exchange => 0, // alohida logika bilan boshqariladi
        };
    }

    /**
     * Bu kirim turimi?
     */
    public function isCredit(): bool
    {
        return in_array($this, [self::Income, self::TransferIn], true);
    }

    /**
     * Bu chiqim turimi?
     */
    public function isDebit(): bool
    {
        return in_array($this, [self::Expense, self::TransferOut], true);
    }
}
