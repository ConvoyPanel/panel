<?php

namespace Convoy\Services\Announcements;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * One entry of the verified feed (docs/announcements-feed.md, "Payload").
 */
final class Announcement
{
    public const TYPES = ['security', 'release', 'maintenance', 'info'];

    public const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    private const ID = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param  list<array{from?: string, before?: string}>|null  $panel
     * @param  list<array{from?: string, before?: string}>|null  $php
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $severity,
        public readonly string $title,
        public readonly string $body,
        public readonly string $url,
        public readonly ?array $panel,
        public readonly ?array $php,
        public readonly CarbonImmutable $publishedAt,
        public readonly ?CarbonImmutable $expiresAt,
    ) {
    }

    /**
     * An entry from the feed, or null with the reason it was refused. Fields
     * the panel doesn't know are ignored, so the console can add new ones.
     *
     * @return array{0: ?self, 1: ?string}
     */
    public static function fromFeed(mixed $entry): array
    {
        if (! is_array($entry)) {
            return [null, 'not an object'];
        }

        $id = $entry['id'] ?? null;

        if (! is_string($id) || strlen($id) > 120 || preg_match(self::ID, $id) !== 1) {
            return [null, 'its id is not a slug'];
        }

        $type = $entry['type'] ?? null;
        $severity = $entry['severity'] ?? null;

        if (! in_array($type, self::TYPES, true)) {
            return [null, 'its type is unknown'];
        }

        if (! is_null($severity) && ! in_array($severity, self::SEVERITIES, true)) {
            return [null, 'its severity is unknown'];
        }

        if ($type === 'security' && is_null($severity)) {
            return [null, 'a security announcement needs a severity'];
        }

        foreach (['title' => 160, 'body' => 4000] as $field => $max) {
            if (! is_string($entry[$field] ?? null) || trim($entry[$field]) === '' || mb_strlen($entry[$field]) > $max) {
                return [null, "its {$field} is missing or too long"];
            }
        }

        if (! Links::allowed($entry['url'] ?? null)) {
            return [null, 'it links outside convoypanel.com and github.com/ConvoyPanel'];
        }

        $affects = $entry['affects'] ?? null;

        if (! is_array($affects)
            || ! VersionRanges::valid($affects['panel'] ?? null)
            || ! VersionRanges::valid($affects['php'] ?? null)) {
            return [null, 'its version ranges are malformed'];
        }

        $publishedAt = self::date($entry['published_at'] ?? null);
        $expiresAt = self::date($entry['expires_at'] ?? null);

        if (is_null($publishedAt) || (! is_null($entry['expires_at'] ?? null) && is_null($expiresAt))) {
            return [null, 'its dates are malformed'];
        }

        return [new self(
            id: $id,
            type: $type,
            severity: $severity,
            title: trim(preg_replace('/\s+/', ' ', $entry['title'])),
            body: Links::stripDisallowed($entry['body']),
            url: $entry['url'],
            panel: $affects['panel'] ?? null,
            php: $affects['php'] ?? null,
            publishedAt: $publishedAt,
            expiresAt: $expiresAt,
        ), null];
    }

    public function expired(?CarbonImmutable $now = null): bool
    {
        return ! is_null($this->expiresAt) && $this->expiresAt->lte($now ?? CarbonImmutable::now());
    }

    /** Critical ones are a banner on every admin page that can't be dismissed. */
    public function critical(): bool
    {
        return $this->severity === 'critical';
    }

    /** The ones mailed to every admin. */
    public function urgent(): bool
    {
        return in_array($this->severity, ['critical', 'high'], true);
    }

    /** @return array<string, mixed> As the feed had it, for the cache. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'severity' => $this->severity,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'affects' => ['panel' => $this->panel, 'php' => $this->php],
            'published_at' => $this->publishedAt->toIso8601ZuluString(),
            'expires_at' => $this->expiresAt?->toIso8601ZuluString(),
        ];
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }
}
