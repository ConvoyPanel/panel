<?php

namespace App\Extensions\Spatie\Data\Proxmox;

/**
 * Codec for Proxmox's "property-list" config format:
 *
 *     <positional head>[,<key>=<value>]*
 *
 * The first comma-segment (the "head") is positional and DTO-specific — for a
 * NIC it is `model[=macaddr]`, for a disk it is the backing volume — so callers
 * handle it explicitly. Everything after it is an order-independent bag of
 * `key=value` pairs, which this codec parses and rebuilds.
 */
class PropertyList
{
    /**
     * Split a raw property-list string into its positional head and the
     * associative `key=value` tail.
     *
     * @return array{0: string, 1: array<string, string>} [head, pairs]
     */
    public static function explode(string $raw): array
    {
        $segments = explode(',', trim($raw));
        $head = trim((string) array_shift($segments));

        $pairs = [];
        foreach ($segments as $segment) {
            if (blank($segment)) {
                continue;
            }

            $kv = explode('=', $segment, 2);
            if (count($kv) === 2) {
                $pairs[trim($kv[0])] = trim($kv[1]);
            }
        }

        return [$head, $pairs];
    }

    /**
     * Rebuild a property-list string from a positional head and a set of
     * `key=value` pairs. Null values are skipped so absent keys never leak an
     * empty pair into the emitted config.
     *
     * @param  array<string, string|null>  $pairs
     */
    public static function implode(string $head, array $pairs): string
    {
        $segments = [$head];

        foreach ($pairs as $key => $value) {
            if ($value === null) {
                continue;
            }

            $segments[] = "{$key}={$value}";
        }

        return implode(',', $segments);
    }
}
