<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => function ($value, $attributes) {
                $type = $attributes['type'] ?? 'string';
                return match($type) {
                    'number' => (float) $value,
                    'boolean' => (bool) $value,
                    'json' => json_decode($value, true),
                    default => $value,
                };
            },
        ];
    }

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set($key, $value, $type = 'string', $description = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
            ]
        );
        return $setting;
    }
}

