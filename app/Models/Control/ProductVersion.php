<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVersion extends Model
{
    protected $table = 'control_product_versions';

    protected $fillable = [
        'product_id',
        'version',
        'release_notes',
        'checksum',
        'download_path',
        'release_date',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
