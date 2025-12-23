<?php

namespace App\Helpers;

if (! function_exists('convertToBytes')) {
    /**
     * Convert a value with a unit suffix (B, KiB, MiB, GiB, TiB) to bytes.
     *
     * @param float $value
     * @param string $unit
     * @return int
     */
    function convertToBytes(float $value, string $unit): int
    {
        $unit = strtoupper(trim($unit));

        return match ($unit) {
            'B' => (int) $value,
            'KIB' => (int) ($value * 1024),
            'MIB' => (int) ($value * 1048576), // pow(1024, 2)
            'GIB' => (int) ($value * 1073741824), // pow(1024, 3)
            'TIB' => (int) ($value * 1099511627776), // pow(1024, 4)
            default => 0,
        };
    }
}
