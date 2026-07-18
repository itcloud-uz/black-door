<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashAccountType;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'note',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CashAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Kassa hisobining balanslari (har bir valyuta uchun).
     */
    public function balances(): HasMany
    {
        return $this->hasMany(CashBalance::class, 'cash_account_id');
    }

    /**
     * Ushbu kassa hisobi orqali o'tgan tranzaksiyalar.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cash_account_id');
    }

    // ──────────────────────────────────────────────
    // Metodlar
    // ──────────────────────────────────────────────

    /**
     * Berilgan valyutadagi balansni olish (sentda/tiyinda).
     */
    public function getBalance(Currency $currency): int
    {
        $balance = $this->balances()->where('currency', $currency->value)->first();

        return $balance?->balance ?? 0;
    }

    /**
     * USD balansi.
     */
    public function usdBalance(): int
    {
        return $this->getBalance(Currency::USD);
    }

    /**
     * UZS balansi.
     */
    public function uzsBalance(): int
    {
        return $this->getBalance(Currency::UZS);
    }
}
