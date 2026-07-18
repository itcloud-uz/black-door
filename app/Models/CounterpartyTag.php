<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CounterpartyTag extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Ushbu tegga ega kontragentlar.
     */
    public function counterparties(): BelongsToMany
    {
        return $this->belongsToMany(Counterparty::class, 'counterparty_tag', 'tag_id', 'counterparty_id');
    }
}
