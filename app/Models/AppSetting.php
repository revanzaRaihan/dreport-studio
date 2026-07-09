<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get setting value by key — cached for 5 minutes.
     */
    public static function getValue(string $key, $default = null): ?string
    {
        $all = Cache::remember(
            'app_settings',
            now()->addMinutes(5),
            fn () => self::all()->pluck('value', 'key')->toArray()   // plain array, safe to serialize
        );
        return $all[$key] ?? $default;
    }

    /**
     * Set setting value by key and bust the settings cache.
     */
    public static function setValue(string $key, ?string $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('app_settings');
    }
}
