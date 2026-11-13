<?php

namespace Convoy\Services\Telemetry;

/**
 * The console's rule for a key in the `resources`, `features` and `views`
 * maps. A key that fails it would get the whole report refused, so the panel
 * leaves it out instead.
 */
final class TelemetryKey
{
    public const PATTERN = '/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/';

    public const MAX_LENGTH = 120;

    public static function valid(string $key): bool
    {
        return strlen($key) <= self::MAX_LENGTH && preg_match(self::PATTERN, $key) === 1;
    }
}
