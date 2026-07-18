<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCheckItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_check_id',
        'product_id',
        'expected_qty',
        'actual_qty',
        'difference',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_qty' => 'integer',
            'actual_qty' => 'integer',
            'difference' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Tegishli inventarizatsiya tekshiruvi.
     */
    public function inventoryCheck(): BelongsTo
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    /**
     * Tekshirilgan mahsulot.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
