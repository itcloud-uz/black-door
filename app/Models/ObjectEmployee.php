<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectEmployee extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'user_id',
        'position',
        'daily_rate_currency',
        'daily_rate',
        'monthly_rate_currency',
        'monthly_rate',
        'hired_at',
        'permissions',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate_currency' => Currency::class,
            'daily_rate' => 'integer',
            'monthly_rate_currency' => Currency::class,
            'monthly_rate' => 'integer',
            'hired_at' => 'date',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Xodim ishlaydigan obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Xodim foydalanuvchisi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Xodimga to'langan maoshlar.
     */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class, 'employee_id');
    }
}
