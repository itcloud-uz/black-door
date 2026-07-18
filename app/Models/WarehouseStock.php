<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_id',
        'product_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Zahira tegishli obyekt.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    /**
     * Zahira mahsuloti.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // ──────────────────────────────────────────────
    // Metodlar
    // ──────────────────────────────────────────────

    /**
     * Zahira minimal darajadan pastmi?
     */
    public function isLow(): bool
    {
        return $this->quantity < $this->product->min_stock_level;
    }
}
