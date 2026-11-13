<?php

namespace Convoy\Services\Updates;

use Carbon\CarbonImmutable;
use Convoy\Services\Announcements\Links;
use Convoy\Services\Announcements\VersionRanges;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The latest Convoy release on GitHub, checked hourly with the announcements
 * and kept in the cache. Requests only read the cache.
 */
class ReleaseChecker
{
    public const CACHE_KEY = 'updates:latest-release';

    private const CACHE_DAYS = 7;

    public function enabled(): bool
    {
        return filled(config('convoy.releases.url'));
    }

    public function refresh(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = Http::withUserAgent('Convoy/'.config('app.version'))
                ->accept('application/vnd.github+json')
                ->connectTimeout(5)
                ->timeout(10)
                ->get((string) config('convoy.releases.url'));
        } catch (ConnectionException $exception) {
            Log::warning('The latest Convoy release could not be checked: '.$exception->getMessage());

            return false;
        }

        $tag = $response->json('tag_name');

        if (! $response->successful() || ! is_string($tag) || ! VersionRanges::comparable($tag)) {
            Log::warning("The latest Convoy release could not be checked: GitHub answered HTTP {$response->status()}.");

            return false;
        }

        $version = ltrim($tag, 'v');
        $url = $response->json('html_url');

        Cache::put(self::CACHE_KEY, [
            'version' => $version,
            'url' => Links::allowed($url) ? $url : "https://github.com/ConvoyPanel/panel/releases/tag/{$tag}",
            'published_at' => $response->json('published_at'),
            'checked_at' => CarbonImmutable::now()->toIso8601ZuluString(),
        ], CarbonImmutable::now()->addDays(self::CACHE_DAYS));

        return true;
    }

    /** @return array{version: string, url: string, published_at: ?string, checked_at: string}|null */
    public function latest(): ?array
    {
        $latest = Cache::get(self::CACHE_KEY);

        return is_array($latest) ? $latest : null;
    }

    /**
     * Whether a newer release than this panel exists, or null when that can't
     * be said: nothing checked yet, or a build from source (`canary`).
     */
    public function updateAvailable(): ?bool
    {
        $current = (string) config('app.version');
        $latest = $this->latest();

        if (is_null($latest) || ! VersionRanges::comparable($current)) {
            return null;
        }

        return version_compare(ltrim($current, 'v'), $latest['version'], '<');
    }
}
