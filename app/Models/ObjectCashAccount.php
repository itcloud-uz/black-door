<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashAccountType;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectCashAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'name',
        'type',
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
     * Kassa hisobi tegishli obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Kassa hisobi balanslari.
     */
    public function balances(): HasMany
    {
        return $this->hasMany(ObjectCashBalance::class, 'object_cash_account_id');
    }

    /**
     * Kassa hisobi tranzaksiyalari.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ObjectTransaction::class, 'object_cash_account_id');
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
}
