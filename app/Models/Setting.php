<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    // ──────────────────────────────────────────────
    // Statik metodlar
    // ──────────────────────────────────────────────

    /**
     * Sozlama qiymatini olish.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "setting.{$key}",
            now()->addHours(24),
            fn () => static::where('key', $key)->first()
        );

        return $setting?->value ?? $default;
    }

    /**
     * Sozlama qiymatini belgilash yoki yangilash.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");
    }
}
