<?php

declare(strict_types=1);

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRequest extends Model
{
    protected $table = 'control_client_requests';

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'email',
        'product_id',
        'tariff_plan_id',
        'status',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(TariffPlan::class, 'tariff_plan_id');
    }
}
