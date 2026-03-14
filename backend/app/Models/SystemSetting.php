<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getInt(string $key, int $default): int
    {
        $value = static::where('key', $key)->value('value');
        if ($value === null || $value === '') {
            return $default;
        }

        return max(0, (int) $value);
    }

    public static function setInt(string $key, int $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) max(0, $value)]
        );
    }
}
