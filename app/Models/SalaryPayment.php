<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryPayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'employee_id',
        'type',
        'currency',
        'amount',
        'period_start',
        'period_end',
        'note',
        'paid_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Maosh to'langan obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Maosh olgan xodim.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(ObjectEmployee::class, 'employee_id');
    }

    /**
     * To'lovni yaratgan foydalanuvchi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
