<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $table = 'control_licenses';

    protected $fillable = [
        'client_id',
        'product_id',
        'tariff_plan_id',
        'license_key',
        'status',
        'starts_at',
        'expires_at',
        'activation_limit',
        'installations_count',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'activation_limit' => 'integer',
        'installations_count' => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class, 'tariff_plan_id');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'license_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LicensePayment::class, 'license_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ControlAuditLog::class, 'license_id');
    }
}
