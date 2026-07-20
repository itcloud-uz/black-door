<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffPlan extends Model
{
    protected $table = 'control_tariff_plans';

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'duration_days',
        'price',
        'currency',
        'max_users',
        'max_objects',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'price' => 'integer',
        'max_users' => 'integer',
        'max_objects' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
