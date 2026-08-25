<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        return Cache::rememberForever(
            static::cacheKey($key),

            function () use ($key, $default) {
                $value = static::query()
                    ->where('key', $key)
                    ->value('value');

                return $value ?? $default;
            }
        );
    }

    public static function set(
        string $key,
        mixed $value
    ): self {
        return static::query()->updateOrCreate(
            [
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget(
                static::cacheKey($setting->key)
            );
        });

        static::deleted(function (self $setting): void {
            Cache::forget(
                static::cacheKey($setting->key)
            );
        });
    }

    protected static function cacheKey(string $key): string
    {
        return "settings.{$key}";
    }
}
