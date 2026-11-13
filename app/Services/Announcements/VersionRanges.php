<?php

namespace Convoy\Services\Announcements;

/**
 * Version ranges as the feed states them (docs/announcements-feed.md): a list
 * of `{from, before}`, either bound optional, `from` inclusive and `before`
 * exclusive. A list matches when any range does; null means every version.
 *
 * Compared with PHP's version_compare after a leading `v` is stripped, so
 * `4.7.0-rc.3` is below `4.7.0` and a range `{before: 4.7.0}` covers the
 * release candidates too.
 */
final class VersionRanges
{
    /** A version a range can be compared with: digits, optionally a pre-release suffix. */
    private const VERSION = '/^v?\d+(?:\.\d+){0,3}(?:-[0-9A-Za-z.]+)?$/';

    /** @param  list<array{from?: string, before?: string}>|null  $ranges */
    public static function matches(?array $ranges, ?string $version): bool
    {
        if (is_null($ranges)) {
            return true;
        }

        if (! self::comparable($version)) {
            return false;
        }

        foreach ($ranges as $range) {
            if (self::inRange($range, $version)) {
                return true;
            }
        }

        return false;
    }

    /** @param  array{from?: string, before?: string}  $range */
    public static function inRange(array $range, string $version): bool
    {
        $version = self::normalize($version);

        if (isset($range['from']) && version_compare($version, self::normalize($range['from']), '<')) {
            return false;
        }

        if (isset($range['before']) && version_compare($version, self::normalize($range['before']), '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Whether a version can be compared at all. `canary` (a build from source)
     * and anything a fork made up can't, so they never match a range.
     */
    public static function comparable(?string $version): bool
    {
        return is_string($version) && preg_match(self::VERSION, $version) === 1;
    }

    /** Whether something from the feed is a valid list of ranges, or null. */
    public static function valid(mixed $ranges): bool
    {
        if (is_null($ranges)) {
            return true;
        }

        if (! is_array($ranges) || ! array_is_list($ranges)) {
            return false;
        }

        foreach ($ranges as $range) {
            if (! is_array($range) || array_diff(array_keys($range), ['from', 'before']) !== []) {
                return false;
            }

            foreach ($range as $bound) {
                if (! is_string($bound) || ! self::comparable($bound)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * "before 8.2.35", "8.3.0 up to 8.3.36", "8.5.0 and later", "every version".
     *
     * @param  list<array{from?: string, before?: string}>|null  $ranges
     */
    public static function describe(?array $ranges): string
    {
        if (is_null($ranges)) {
            return 'every version';
        }

        if ($ranges === []) {
            return 'no version';
        }

        return implode(', ', array_map(fn (array $range) => match (true) {
            isset($range['from'], $range['before']) => self::normalize($range['from']).' up to '.self::normalize($range['before']),
            isset($range['before']) => 'before '.self::normalize($range['before']),
            isset($range['from']) => self::normalize($range['from']).' and later',
            default => 'every version',
        }, $ranges));
    }

    private static function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
