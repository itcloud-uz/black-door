<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientLicense extends Model
{
    protected $table = 'client_licenses';

    protected $fillable = [
        'license_key',
        'tariff_plan_code',
        'client_name',
        'starts_at',
        'expires_at',
        'max_users',
        'max_objects',
        'features',
        'installation_uuid',
        'status',
        'token_payload',
        'token_signature',
        'last_successful_heartbeat_at',
        'is_read_only_grace',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'max_users' => 'integer',
        'max_objects' => 'integer',
        'features' => 'array',
        'last_successful_heartbeat_at' => 'datetime',
        'is_read_only_grace' => 'boolean',
    ];

    /**
     * Check if feature flag is active
     */
    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]) && (bool)$this->features[$feature] === true;
    }
}
