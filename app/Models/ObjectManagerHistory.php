<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectManagerHistory extends Model
{
    /**
     * @var string
     */
    protected $table = 'object_manager_history';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'user_id',
        'assigned_at',
        'unassigned_at',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tarixdagi obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Tarixdagi foydalanuvchi (menejer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
