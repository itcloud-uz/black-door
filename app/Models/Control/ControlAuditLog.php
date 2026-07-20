<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAuditLog extends Model
{
    protected $table = 'control_audit_logs';

    protected $fillable = [
        'license_id',
        'action',
        'old_values',
        'new_values',
        'performed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }
}
