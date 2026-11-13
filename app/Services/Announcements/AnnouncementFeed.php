<?php

namespace Convoy\Services\Announcements;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * The signed announcements feed (docs/announcements-feed.md): fetched hourly by
 * `announcements:check`, verified, and kept in the cache for 7 days. Requests
 * only ever read the cache.
 *
 * A feed that can't be fetched or doesn't verify leaves the last verified copy
 * in place. Nothing unverified is ever stored or shown.
 */
class AnnouncementFeed
{
    public const CACHE_KEY = 'announcements:feed';

    /** The last check that failed, for Settings → Updates. Cleared by the next one that works. */
    public const ERROR_KEY = 'announcements:feed:error';

    public const CACHE_DAYS = 7;

    public function enabled(): bool
    {
        return filled(config('convoy.announcements.url'));
    }

    /**
     * Fetches the feed and stores it once it verifies.
     *
     * @return bool Whether the panel now holds a copy verified by this check
     */
    public function refresh(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $cached = $this->cached();

        $request = Http::withUserAgent('Convoy/'.config('app.version'))
            ->accept('application/json')
            ->connectTimeout(5)
            ->timeout(10);

        if (filled($cached['etag'] ?? null)) {
            $request->withHeaders(['If-None-Match' => $cached['etag']]);
        }

        try {
            $response = $request->get((string) config('convoy.announcements.url'));
        } catch (ConnectionException $exception) {
            return $this->failed('The announcements feed could not be reached: '.$exception->getMessage());
        }

        if ($response->status() === 304 && ! is_null($cached)) {
            $this->store([...$cached, 'checked_at' => CarbonImmutable::now()->toIso8601ZuluString()]);

            return true;
        }

        if (! $response->successful()) {
            return $this->failed("The announcements feed answered HTTP {$response->status()}.");
        }

        try {
            $payload = $this->verify($response->body());
        } catch (InvalidFeedException $exception) {
            return $this->failed('The announcements feed was refused: '.$exception->getMessage());
        }

        $generatedAt = CarbonImmutable::parse($payload['generated_at'])->utc();

        // A copy older than the one already verified would bring back what was since withdrawn or
        // hide what was since published. A cache in front of the console can serve one.
        if (! is_null($cached) && $generatedAt->lt(CarbonImmutable::parse($cached['generated_at']))) {
            return $this->failed('The announcements feed is older than the copy this panel already has.');
        }

        $announcements = [];

        foreach ($payload['announcements'] as $entry) {
            [$announcement, $reason] = Announcement::fromFeed($entry);

            if (is_null($announcement)) {
                Log::warning('An announcement in the feed was skipped: '.$reason.'.', [
                    'id' => is_array($entry) && is_string($entry['id'] ?? null) ? $entry['id'] : null,
                ]);

                continue;
            }

            $announcements[] = $announcement->toArray();
        }

        $this->store([
            'etag' => $response->header('ETag') ?: null,
            'generated_at' => $generatedAt->toIso8601ZuluString(),
            'checked_at' => CarbonImmutable::now()->toIso8601ZuluString(),
            'announcements' => $announcements,
        ]);

        return true;
    }

    /**
     * Checks the envelope's signature and returns the payload it covers. The
     * signature is over the payload's JSON as signed, that is the base64-decoded
     * bytes, which are verified before anything is parsed.
     *
     * @return array{generated_at: string, announcements: list<mixed>}
     *
     * @throws InvalidFeedException
     */
    public function verify(string $body): array
    {
        try {
            $envelope = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidFeedException('it is not JSON.');
        }

        if (! is_array($envelope)
            || ! is_string($envelope['key_id'] ?? null)
            || ! is_string($envelope['signature'] ?? null)
            || ! is_string($envelope['payload'] ?? null)) {
            throw new InvalidFeedException('it is not a signed envelope.');
        }

        $keys = (array) config('convoy.announcements.keys');
        $publicKey = $keys[$envelope['key_id']] ?? null;

        if (! is_string($publicKey)) {
            throw new InvalidFeedException("it is signed with key \"{$envelope['key_id']}\", which this panel doesn't know.");
        }

        $publicKey = base64_decode($publicKey, true);
        $signature = base64_decode($envelope['signature'], true);
        $payload = base64_decode($envelope['payload'], true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new InvalidFeedException("the public key for \"{$envelope['key_id']}\" is not an Ed25519 key.");
        }

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES || $payload === false) {
            throw new InvalidFeedException('its signature or payload is not base64.');
        }

        if (! sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
            throw new InvalidFeedException('its signature does not match.');
        }

        try {
            $data = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidFeedException('its payload is not JSON.');
        }

        if (! is_array($data)
            || ! is_string($data['generated_at'] ?? null)
            || ! is_array($data['announcements'] ?? null)
            || ! array_is_list($data['announcements'])
            || rescue(fn () => CarbonImmutable::parse($data['generated_at']), null, false) === null) {
            throw new InvalidFeedException('its payload is not an announcements document.');
        }

        return $data;
    }

    /**
     * The last verified copy, or null when there is none (never fetched, turned
     * off, or older than 7 days).
     *
     * @return array{etag: ?string, generated_at: string, checked_at: string, announcements: list<array<string, mixed>>}|null
     */
    public function cached(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached : null;
    }

    /** @return list<Announcement> newest first */
    public function announcements(): array
    {
        $announcements = [];

        foreach ($this->cached()['announcements'] ?? [] as $entry) {
            if ($announcement = Announcement::fromFeed($entry)[0]) {
                $announcements[] = $announcement;
            }
        }

        usort($announcements, fn (Announcement $a, Announcement $b) => $b->publishedAt <=> $a->publishedAt);

        return $announcements;
    }

    /** @return array{message: string, at: string}|null */
    public function lastError(): ?array
    {
        $error = Cache::get(self::ERROR_KEY);

        return is_array($error) ? $error : null;
    }

    /** @param  array<string, mixed>  $feed */
    private function store(array $feed): void
    {
        Cache::put(self::CACHE_KEY, $feed, CarbonImmutable::now()->addDays(self::CACHE_DAYS));
        Cache::forget(self::ERROR_KEY);
    }

    private function failed(string $message): bool
    {
        Log::warning($message.' The last verified copy is kept.');

        Cache::put(self::ERROR_KEY, [
            'message' => $message,
            'at' => CarbonImmutable::now()->toIso8601ZuluString(),
        ], CarbonImmutable::now()->addDays(self::CACHE_DAYS));

        return false;
    }
}
