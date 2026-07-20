<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicensePayment extends Model
{
    protected $table = 'control_license_payments';

    protected $fillable = [
        'license_id',
        'payment_date',
        'amount',
        'currency',
        'payment_method',
        'attachment_path',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }
}
