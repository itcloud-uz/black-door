<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectCashBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_cash_account_id',
        'currency',
        'balance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'balance' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tegishli obyekt kassa hisobi.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ObjectCashAccount::class, 'object_cash_account_id');
    }

    // ──────────────────────────────────────────────
    // Aksessorlar (Accessors)
    // ──────────────────────────────────────────────

    /**
     * Formatlangan balans.
     */
    protected function formattedBalance(): Attribute
    {
        return Attribute::get(fn () => $this->currency->format($this->balance));
    }

    /**
     * Amount accessor mapping to balance (for view compatibility).
     */
    protected function amount(): Attribute
    {
        return Attribute::get(fn () => $this->balance);
    }
}
