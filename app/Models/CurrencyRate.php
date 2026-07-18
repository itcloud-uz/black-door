<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rate_uzs_per_usd',
        'set_by',
        'effective_date',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_uzs_per_usd' => 'integer',
            'effective_date' => 'date',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Kursni belgilagan foydalanuvchi.
     */
    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * User relation alias for setter.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Rate accessor converting tiyin to integer UZS.
     */
    protected function rate(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => (int)($this->rate_uzs_per_usd / 100));
    }

    // ──────────────────────────────────────────────
    // Qamrovlar (Scopes)
    // ──────────────────────────────────────────────

    /**
     * Eng so'nggi valyuta kursi.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('effective_date');
    }
}
