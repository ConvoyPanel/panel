<?php

namespace Convoy\Models;

/**
 * A panel-wide value that isn't configuration an administrator edits in .env,
 * such as the telemetry installation id.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static array $validationRules = [
        'key' => 'required|string|max:191',
        'value' => 'nullable|string',
    ];

    public static function get(string $key): ?string
    {
        return static::query()->whereKey($key)->value('value');
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function forget(string $key): void
    {
        static::query()->whereKey($key)->delete();
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
