<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /**
     * Audit loglar o'chirilmaydi — doimiy saqlash.
     */

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'as_sub_manager',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'as_sub_manager' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Amalni bajargan foydalanuvchi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audit qilingan model (polimorfik).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Changes accessor combining old and new values.
     */
    protected function changes(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            if ($this->old_values === null && $this->new_values === null) {
                return null;
            }
            return [
                'eski' => $this->old_values,
                'yangi' => $this->new_values,
            ];
        });
    }
}
