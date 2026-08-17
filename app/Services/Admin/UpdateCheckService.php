<?php

namespace App\Services\Admin;

use App\Console\Commands\Maintenance\CheckForUpdatesCommand;
use App\Data\Admin\UpdateStatusData;
use App\Enums\UpdateStatus;
use App\Exceptions\Http\Admin\UpdateCheckFailedException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Tells the panel whether a newer release of itself exists.
 *
 * The check runs in the background ({@see CheckForUpdatesCommand})
 * and the result is cached; requests only ever read that cache. Same rule as
 * node status and Anchor liveness — an admin screen must never block on a third
 * party being reachable, and GitHub being slow should not slow the panel down.
 *
 * Only published, non-prerelease releases count. GitHub's `/releases/latest`
 * already excludes drafts and prereleases, which matches how the panel ships:
 * the release workflow opens a draft, so a tag alone never announces an update.
 */
class UpdateCheckService
{
    /**
     * The last successful check. Holds the release, not the comparison — the
     * running version is read fresh every time, so an upgrade reports correctly
     * without waiting for the next check to overwrite this.
     */
    public const CACHE_KEY = 'panel:update-check';

    /**
     * Far longer than the hourly schedule, so a check has to fail for days
     * before the panel forgets what it knew. It expires at all only so a panel
     * whose scheduler has stopped eventually stops claiming to be current.
     */
    private const CACHE_DAYS = 7;

    private const TIMEOUT_SECONDS = 10;

    /**
     * What a source checkout reports, since the release workflow is what
     * rewrites `config/app.php` with the tag. There is no sensible comparison
     * between a git tree and a release, so these panels report UNKNOWN.
     */
    private const DEVELOPMENT_VERSION = 'canary';

    /**
     * The cached view of the latest release. Never performs a request, so it is
     * safe on any code path a browser can reach.
     */
    public function status(): UpdateStatusData
    {
        return $this->toStatus(Cache::get(self::CACHE_KEY));
    }

    /**
     * Fetches the newest published release and caches it.
     *
     * A failed fetch leaves the previous result in place rather than blanking
     * it: a transient GitHub outage should not make an out-of-date panel look
     * up to date.
     *
     * @throws UpdateCheckFailedException when the release cannot be fetched or parsed
     */
    public function check(): UpdateStatusData
    {
        $release = $this->fetchLatestRelease();

        Cache::put(self::CACHE_KEY, $release, now()->addDays(self::CACHE_DAYS));

        return $this->toStatus($release);
    }

    /**
     * @return array{version: string, url: string|null, releasedAt: string|null, checkedAt: string}
     *
     * @throws UpdateCheckFailedException
     */
    private function fetchLatestRelease(): array
    {
        $repository = config('convoy.updates.repository');
        $url = "https://api.github.com/repos/{$repository}/releases/latest";

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                    // GitHub rejects requests without one, and an identifiable
                    // agent is the courteous way to consume a public API.
                    'User-Agent' => 'Convoy/'.config('app.version'),
                ])
                ->get($url);
        } catch (Throwable $exception) {
            throw new UpdateCheckFailedException(
                "Could not reach {$url}: {$exception->getMessage()}",
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new UpdateCheckFailedException("{$url} responded with HTTP {$response->status()}.");
        }

        $tag = (string) $response->json('tag_name');

        if ($tag === '') {
            throw new UpdateCheckFailedException("{$url} returned a release without a tag name.");
        }

        return [
            'version' => $this->normalize($tag),
            'url' => $response->json('html_url'),
            'releasedAt' => $response->json('published_at'),
            'checkedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array{version: string, url: string|null, releasedAt: string|null, checkedAt: string}|null  $release
     */
    private function toStatus(?array $release): UpdateStatusData
    {
        $current = (string) config('app.version');
        $latest = $release === null ? null : Arr::get($release, 'version');

        $comparable = $latest !== null && $current !== self::DEVELOPMENT_VERSION;
        $updateAvailable = $comparable
            && version_compare($latest, $this->normalize($current), '>');

        return new UpdateStatusData(
            currentVersion: $current,
            latestVersion: $latest,
            releaseUrl: $release === null ? null : Arr::get($release, 'url'),
            releasedAt: $release === null ? null : Arr::get($release, 'releasedAt'),
            checkedAt: $release === null ? null : Arr::get($release, 'checkedAt'),
            repository: (string) config('convoy.updates.repository'),
            updateAvailable: $updateAvailable,
            status: match (true) {
                ! $comparable => UpdateStatus::UNKNOWN,
                $updateAvailable => UpdateStatus::UPDATE_AVAILABLE,
                default => UpdateStatus::UP_TO_DATE,
            },
        );
    }

    /**
     * Tags are cut as `v4.6.1` but the embedded version is written without the
     * prefix, so both sides are stripped before `version_compare` sees them.
     * It already orders `4.6.1-rc.1` below `4.6.1`, which is what an install
     * running a release candidate should be told.
     */
    private function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
