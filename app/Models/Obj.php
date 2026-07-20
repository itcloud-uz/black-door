<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obj extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 'Object' PHP-da zahiralangan so'z, shuning uchun jadval nomi alohida ko'rsatiladi.
     *
     * @var string
     */
    protected $table = 'objects';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'address',
        'note',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ObjectType::class,
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Obyektning joriy menejeri (1:1).
     */
    public function currentManager(): HasOne
    {
        return $this->hasOne(ObjectManager::class, 'object_id');
    }

    /**
     * Obyektning faol menejeri (currentManager uchun alias).
     */
    public function activeManager(): HasOne
    {
        return $this->hasOne(ObjectManager::class, 'object_id');
    }

    /**
     * Menejer foydalanuvchisi (ObjectManager orqali).
     */
    public function manager(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            ObjectManager::class,
            'object_id', // object_managers.object_id
            'id',         // users.id
            'id',         // objects.id
            'user_id'     // object_managers.user_id
        );
    }

    /**
     * Menejer tayinlash tarixi.
     */
    public function managerHistory(): HasMany
    {
        return $this->hasMany(ObjectManagerHistory::class, 'object_id');
    }

    /**
     * Obyektning vaqtinchalik o'rinbosar menejerlari.
     */
    public function subManagers(): HasMany
    {
        return $this->hasMany(ObjectSubManager::class, 'object_id');
    }

    /**
     * Obyekt xodimlari.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(ObjectEmployee::class, 'object_id');
    }

    /**
     * Obyekt kassa hisoblari.
     */
    public function cashAccounts(): HasMany
    {
        return $this->hasMany(ObjectCashAccount::class, 'object_id');
    }

    /**
     * Obyekt tranzaksiyalari.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ObjectTransaction::class, 'object_id');
    }

    /**
     * Ombor zahiralari.
     */
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'object_id');
    }

    /**
     * Ombor harakatlari.
     */
    public function warehouseMovements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class, 'object_id');
    }

    /**
     * Inventarizatsiya tekshiruvlari.
     */
    public function inventoryChecks(): HasMany
    {
        return $this->hasMany(InventoryCheck::class, 'object_id');
    }
}
