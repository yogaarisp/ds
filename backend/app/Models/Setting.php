<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $all = static::getAll();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        \Illuminate\Support\Facades\Cache::forget('app_settings_all');
    }

    public static function getAll(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('app_settings_all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}
