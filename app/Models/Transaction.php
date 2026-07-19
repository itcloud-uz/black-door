<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cash_account_id',
        'counterparty_id',
        'category_id',
        'type',
        'currency',
        'amount',
        'balance_after',
        'note',
        'attachment_path',
        'created_by',
        'related_transaction_id',
        'transaction_date',
        'exchange_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'currency' => Currency::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'transaction_date' => 'date',
            'exchange_rate' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tranzaksiya o'tgan kassa hisobi.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    /**
     * Tranzaksiya kontragenti.
     */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    /**
     * Tranzaksiya kategoriyasi.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Tranzaksiyani yaratgan foydalanuvchi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Bog'langan tranzaksiya (o'tkazma/ayirboshlash juftligi uchun).
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_id');
    }
}
