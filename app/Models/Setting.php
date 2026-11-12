<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const string CacheKey = 'system-settings';

    /**
     * Every saved setting, keyed by name.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CacheKey, fn (): array => static::query()->pluck('value', 'key')->all());
    }

    public static function read(string $key): ?string
    {
        return static::values()[$key] ?? null;
    }

    public static function isSaved(string $key): bool
    {
        return array_key_exists($key, static::values());
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CacheKey);
    }
}
