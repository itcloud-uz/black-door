<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'control_products';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class, 'product_id');
    }

    public function tariffPlans(): HasMany
    {
        return $this->hasMany(TariffPlan::class, 'product_id');
    }
}
