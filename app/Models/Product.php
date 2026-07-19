<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'unit',
        'min_stock_level',
        'min_limit',
        'note',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'min_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'min_stock_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Mahsulot ombor zahiralari (har bir obyekt uchun).
     */
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'product_id');
    }

    /**
     * Mahsulot ombor harakatlari.
     */
    public function warehouseMovements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class, 'product_id');
    }

    /**
     * Get the min_limit alias for min_stock_level.
     */
    public function getMinLimitAttribute(): int
    {
        return (int) $this->min_stock_level;
    }

    /**
     * Set the min_limit alias for min_stock_level.
     */
    public function setMinLimitAttribute(mixed $value): void
    {
        $this->attributes['min_stock_level'] = $value;
    }
}
