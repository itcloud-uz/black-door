<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCheck extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'checked_by',
        'checked_at',
        'status',
        'note',
        'approved_by',
        'approved_at',
        'as_sub_manager',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'approved_at' => 'datetime',
            'as_sub_manager' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tekshiruv o'tgan obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Tekshiruvni amalga oshirgan foydalanuvchi.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * Tekshiruvni tasdiqlagan foydalanuvchi.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Tekshiruv elementlari.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryCheckItem::class, 'inventory_check_id');
    }
}
