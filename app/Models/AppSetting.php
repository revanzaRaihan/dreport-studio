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

    public function getValueAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (in_array($this->key, ['ai_api_key', 'gemini_api_key', 'groq_api_key', 'admin_wa_number'])) {
            try {
                return decrypt($value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return $value;
            }
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        if (in_array($this->key, ['ai_api_key', 'gemini_api_key', 'groq_api_key', 'admin_wa_number'])) {
            $this->attributes['value'] = encrypt($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['key']) && in_array($array['key'], ['ai_api_key', 'gemini_api_key', 'groq_api_key', 'admin_wa_number'])) {
            $array['value'] = '********';
        }
        return $array;
    }

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
