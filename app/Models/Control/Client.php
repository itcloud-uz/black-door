<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'control_clients';

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'telegram',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'client_id');
    }
}
