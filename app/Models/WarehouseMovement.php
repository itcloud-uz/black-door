<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarehouseMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseMovement extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'product_id',
        'type',
        'quantity',
        'from_object_id',
        'to_object_id',
        'note',
        'recipient_name',
        'created_by',
        'movement_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WarehouseMovementType::class,
            'quantity' => 'integer',
            'movement_date' => 'date',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Harakat tegishli obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Harakat mahsuloti.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * O'tkazma: qaysi obyektdan (jo'natuvchi).
     */
    public function fromObject(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'from_object_id');
    }

    /**
     * O'tkazma: qaysi obyektga (qabul qiluvchi).
     */
    public function toObject(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'to_object_id');
    }

    /**
     * Harakatni yaratgan foydalanuvchi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
