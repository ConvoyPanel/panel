<?php

namespace Convoy\Services\Telemetry;

use Illuminate\Support\Facades\Cache;

/**
 * Page views since the last report the console accepted, one cache counter per
 * page key.
 *
 * Nothing is reset by reading. The report sends what {@see peek()} returned,
 * and only once the console has accepted it does {@see subtract()} take those
 * exact amounts back off. A view recorded while the report was in flight stays
 * counted for the next one, and a failed send loses nothing. Both operations
 * are single atomic increments, so no view is counted twice or dropped.
 */
class ViewCounter
{
    /** The console's limit on keys per map. */
    public const MAX_KEYS = 200;

    private const PREFIX = 'telemetry:views:';

    /** The keys that have a counter, so a report can read them without scanning the store. */
    private const INDEX = 'telemetry:views';

    public function record(string $key, int $count = 1): void
    {
        if ($count < 1 || ! TelemetryKey::valid($key)) {
            return;
        }

        $index = $this->keys();

        if (! in_array($key, $index, true)) {
            if (count($index) >= self::MAX_KEYS) {
                return;
            }

            $this->index($key);
        }

        Cache::increment(self::PREFIX.$key, $count);
    }

    /** @return array<string, int> page key => views, only those above zero */
    public function peek(): array
    {
        $keys = $this->keys();

        if ($keys === []) {
            return [];
        }

        $counts = [];

        foreach (Cache::many(array_map(fn (string $key) => self::PREFIX.$key, $keys)) as $cacheKey => $count) {
            if ((int) $count > 0) {
                $counts[substr($cacheKey, strlen(self::PREFIX))] = (int) $count;
            }
        }

        ksort($counts);

        return $counts;
    }

    /** @param  array<string, int>  $counts  what a report the console accepted carried */
    public function subtract(array $counts): void
    {
        foreach ($counts as $key => $count) {
            Cache::decrement(self::PREFIX.$key, $count);
        }
    }

    public function clear(): void
    {
        foreach ($this->keys() as $key) {
            Cache::forget(self::PREFIX.$key);
        }

        Cache::forget(self::INDEX);
    }

    /** @return list<string> */
    private function keys(): array
    {
        return array_values((array) Cache::get(self::INDEX, []));
    }

    /**
     * A page seen for the first time since the last {@see clear()}. Rare, so a
     * lock is cheap here. If another request holds it, the next view of this
     * page adds the key instead. Its counter is incremented either way, so the
     * views are not lost, only reported a day later.
     */
    private function index(string $key): void
    {
        Cache::lock(self::INDEX.':lock', 5)->get(function () use ($key) {
            $index = $this->keys();

            if (! in_array($key, $index, true) && count($index) < self::MAX_KEYS) {
                $index[] = $key;
                Cache::forever(self::INDEX, $index);
            }
        });
    }
}
