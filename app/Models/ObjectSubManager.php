<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectSubManager extends Model
{
    protected $table = 'object_sub_managers';

    protected $fillable = [
        'object_id',
        'user_id',
        'start_date',
        'end_date',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'processed' => 'boolean',
        ];
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(Obj::class, 'object_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function isCurrentUserSubManager(int $objectId): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $userId = auth()->id();
        
        // If they are the primary manager, they are not acting as a sub-manager.
        $isPrimary = ObjectManager::where('object_id', $objectId)
            ->where('user_id', $userId)
            ->exists();

        if ($isPrimary) {
            return false;
        }

        $today = today();
        return self::where('object_id', $objectId)
            ->where('user_id', $userId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();
    }
}
