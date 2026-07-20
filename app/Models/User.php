<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'pin_code',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'failed_pin_attempts',
        'pin_locked_until',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
            'pin_locked_until' => 'datetime',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Munosabatlar (Relationships)
    // ──────────────────────────────────────────────

    /**
     * Foydalanuvchi boshqaradigan obyekt (menejer sifatida).
     */
    public function managedObject(): HasOne
    {
        return $this->hasOne(ObjectManager::class, 'user_id');
    }

    /**
     * Foydalanuvchining o'rinbosarlik munosabatlari.
     */
    public function subManagers(): HasMany
    {
        return $this->hasMany(ObjectSubManager::class, 'user_id');
    }

    /**
     * Foydalanuvchiga biriktirilgan barcha faol (asosiy va vaqtinchalik) obyekt IDlarini olish.
     */
    public function getManagedObjectIds(): array
    {
        $ids = [];
        
        $primary = ObjectManager::where('user_id', $this->id)->value('object_id');
        if ($primary) {
            $ids[] = (int)$primary;
        }
        
        $today = today();
        $subManaged = ObjectSubManager::where('user_id', $this->id)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->pluck('object_id')
            ->map(fn($val) => (int)$val)
            ->toArray();
            
        return array_unique(array_merge($ids, $subManaged));
    }

    /**
     * Foydalanuvchi obyektdagi xodim sifatida.
     */
    public function objectEmployee(): HasOne
    {
        return $this->hasOne(ObjectEmployee::class, 'user_id');
    }

    /**
     * Foydalanuvchi audit loglari.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Foydalanuvchi yaratgan tranzaksiyalar (moliya moduli).
     */
    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    // ──────────────────────────────────────────────
    // Qamrovlar (Scopes)
    // ──────────────────────────────────────────────

    /**
     * Faqat faol foydalanuvchilar.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Ma'lum bir rol bo'yicha filtrlash.
     */
    public function scopeRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role', $role->value);
    }

    /**
     * Faqat super adminlar.
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::SuperAdmin->value);
    }

    /**
     * Faqat moliyachilar.
     */
    public function scopeFinanciers(Builder $query): Builder
    {
        return $query->where('role', UserRole::Financier->value);
    }

    /**
     * Faqat menejerlar.
     */
    public function scopeManagers(Builder $query): Builder
    {
        return $query->where('role', UserRole::Manager->value);
    }

    /**
     * Faqat xodimlar.
     */
    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('role', UserRole::Employee->value);
    }

    // ──────────────────────────────────────────────
    // Metodlar
    // ──────────────────────────────────────────────

    /**
     * Super admin ekanligini tekshirish.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Moliyachi ekanligini tekshirish.
     */
    public function isFinancier(): bool
    {
        return $this->role === UserRole::Financier;
    }

    /**
     * Menejer ekanligini tekshirish.
     */
    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    /**
     * Xodim ekanligini tekshirish.
     */
    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    /**
     * Moliya moduliga kirish huquqi bormi.
     */
    public function canAccessFinance(): bool
    {
        return $this->role->canAccessFinance();
    }

    /**
     * PIN kodni tekshirish.
     */
    public function hasValidPin(string $pin): bool
    {
        if ($this->pin_code === null) {
            return false;
        }

        return Hash::check($pin, $this->pin_code);
    }
}
