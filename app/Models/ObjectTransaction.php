<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'object_cash_account_id',
        'category_id',
        'counterparty_name',
        'type',
        'currency',
        'amount',
        'balance_after',
        'note',
        'attachment_path',
        'created_by',
        'transaction_date',
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
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tranzaksiya tegishli obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Tranzaksiya kassa hisobi.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ObjectCashAccount::class, 'object_cash_account_id');
    }

    /**
     * Tranzaksiya kategoriyasi.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ObjectTransactionCategory::class, 'category_id');
    }

    /**
     * Tranzaksiyani yaratgan foydalanuvchi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
