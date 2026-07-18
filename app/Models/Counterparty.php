<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CounterpartyCategory;
use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Counterparty extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'note',
        'category',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CounterpartyCategory::class,
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Kontragentga biriktirilgan teglar.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CounterpartyTag::class, 'counterparty_tag', 'counterparty_id', 'tag_id');
    }

    /**
     * Kontragent bilan bog'liq tranzaksiyalar.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'counterparty_id');
    }

    /**
     * Kontragentni yaratgan foydalanuvchi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────────────────────────────────
    // Metodlar
    // ──────────────────────────────────────────────

    /**
     * USD bo'yicha balansni hisoblash (sentda).
     * Kirimlar - chiqimlar = balans.
     */
    public function getBalanceUsd(): int
    {
        return $this->calculateBalance(Currency::USD);
    }

    /**
     * UZS bo'yicha balansni hisoblash (tiyinda).
     */
    public function getBalanceUzs(): int
    {
        return $this->calculateBalance(Currency::UZS);
    }

    /**
     * Berilgan valyuta bo'yicha balansni hisoblash.
     */
    private function calculateBalance(Currency $currency): int
    {
        $transactions = $this->transactions()
            ->where('currency', $currency->value)
            ->get();

        $balance = 0;
        foreach ($transactions as $transaction) {
            $direction = $transaction->type->balanceDirection();
            $balance += $transaction->amount * $direction;
        }

        return $balance;
    }

    // ──────────────────────────────────────────────
    // Aksessorlar (Accessors)
    // ──────────────────────────────────────────────

    /**
     * USD balansni formatlangan ko'rinishda.
     */
    protected function balanceUsdFormatted(): Attribute
    {
        return Attribute::get(fn () => Currency::USD->format($this->getBalanceUsd()));
    }

    /**
     * UZS balansni formatlangan ko'rinishda.
     */
    protected function balanceUzsFormatted(): Attribute
    {
        return Attribute::get(fn () => Currency::UZS->format($this->getBalanceUzs()));
    }
}
