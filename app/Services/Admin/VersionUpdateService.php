<?php

namespace Convoy\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class VersionUpdateService
{
    private const CANARY_VERSION = 'canary';

    private const CACHE_KEY = 'admin:version-update:github-release';

    private const SUCCESS_CACHE_SECONDS = 3600;

    private const FAILURE_CACHE_SECONDS = 300;

    private const LATEST_RELEASE_URL = 'https://api.github.com/repos/ConvoyPanel/panel/releases/latest';

    public function status(): array
    {
        $currentVersion = trim((string) config('app.version', self::CANARY_VERSION));
        $normalizedCurrentVersion = $this->normalizeVersion($currentVersion);

        if ($this->isCanary($currentVersion)) {
            return $this->result('canary', $currentVersion);
        }

        if (! $this->isComparableVersion($normalizedCurrentVersion)) {
            return $this->result('unknown', $currentVersion);
        }

        $latestRelease = $this->latestRelease();
        $latestVersion = $latestRelease['version'];
        $normalizedLatestVersion = $this->normalizeVersion($latestVersion);

        if (! $latestVersion) {
            return $this->result('unavailable', $currentVersion);
        }

        if (! $this->isComparableVersion($normalizedLatestVersion)) {
            return $this->result(
                'unavailable',
                $currentVersion,
                $latestVersion,
                $latestRelease['url'],
            );
        }

        $comparison = version_compare(
            $normalizedCurrentVersion,
            $normalizedLatestVersion,
        );
        $isOutdated = $comparison < 0;

        return $this->result(
            match (true) {
                $isOutdated => 'outdated',
                $comparison > 0 => 'ahead',
                default => 'current',
            },
            $currentVersion,
            $latestVersion,
            $latestRelease['url'],
            $isOutdated,
        );
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function latestRelease(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $latestRelease = $this->fetchLatestRelease();

        Cache::put(
            self::CACHE_KEY,
            $latestRelease,
            $latestRelease['version']
                ? self::SUCCESS_CACHE_SECONDS
                : self::FAILURE_CACHE_SECONDS,
        );

        return $latestRelease;
    }

    private function fetchLatestRelease(): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'ConvoyPanel Update Checker',
            ])
                ->timeout((int) config('convoy.guzzle.timeout', 15))
                ->get(self::LATEST_RELEASE_URL);

            if ($response->successful()) {
                $version = $response->json('tag_name');
                $url = $response->json('html_url');

                return [
                    'version' => is_string($version) ? $version : null,
                    'url' => is_string($url) ? $url : null,
                ];
            }
        } catch (Throwable) {
            // Network failures are surfaced as an unavailable update status.
        }

        return [
            'version' => null,
            'url' => null,
        ];
    }

    private function result(
        string $status,
        string $currentVersion,
        ?string $latestVersion = null,
        ?string $releaseUrl = null,
        bool $isOutdated = false,
    ): array {
        return [
            'status' => $status,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'is_outdated' => $isOutdated,
            'release_url' => $releaseUrl,
        ];
    }

    private function isCanary(string $version): bool
    {
        return strtolower(trim($version)) === self::CANARY_VERSION;
    }

    private function normalizeVersion(?string $version): ?string
    {
        $version = trim((string) $version);

        return $version === '' ? null : preg_replace('/^v(?=\d)/i', '', $version);
    }

    private function isComparableVersion(?string $version): bool
    {
        return $version !== null && preg_match('/^\d+(?:\.\d+)+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1;
    }
}
