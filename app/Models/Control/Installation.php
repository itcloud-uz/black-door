<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    protected $table = 'control_installations';

    protected $fillable = [
        'license_id',
        'hardware_uuid',
        'domain',
        'ip_address',
        'last_seen_at',
        'metadata',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }
}
