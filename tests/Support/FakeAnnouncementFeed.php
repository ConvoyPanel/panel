<?php

namespace Tests\Support;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/**
 * A feed signed the way the console signs it (docs/announcements-feed.md),
 * with a keypair made for the test run and installed as the only key the panel
 * trusts.
 */
final class FakeAnnouncementFeed
{
    public const URL = 'https://console.test/api/v1/announcements';

    public const KEY_ID = 'test-2026';

    private static ?string $secret = null;

    private static ?string $public = null;

    public static function install(): void
    {
        self::keys();

        config([
            'convoy.announcements.url' => self::URL,
            'convoy.announcements.keys' => [self::KEY_ID => base64_encode(self::$public)],
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    public static function entry(array $overrides = []): array
    {
        return array_replace([
            'id' => '2026-10-21-php2shell',
            'type' => 'security',
            'severity' => 'critical',
            'title' => 'PHP2Shell: your panel\'s PHP needs patching',
            'body' => 'Run `convoy upgrade`, or follow the steps in the [announcement](https://github.com/ConvoyPanel/panel/discussions/180).',
            'url' => 'https://github.com/ConvoyPanel/panel/discussions/180',
            'affects' => ['panel' => null, 'php' => null],
            'published_at' => '2026-10-21T16:30:00Z',
            'expires_at' => null,
        ], $overrides);
    }

    /**
     * The payload JSON exactly as signed.
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public static function payload(array $entries, string $generatedAt = '2026-11-01T00:00:00Z'): string
    {
        return json_encode([
            'generated_at' => $generatedAt,
            'announcements' => $entries,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** The envelope around a payload, signed over the payload's bytes as the console does. */
    public static function envelope(string $payload, string $keyId = self::KEY_ID, ?string $secret = null): string
    {
        self::keys();

        return json_encode([
            'key_id' => $keyId,
            'signature' => base64_encode(sodium_crypto_sign_detached($payload, $secret ?? self::$secret)),
            'payload' => base64_encode($payload),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Serves these entries, signed, at the feed's URL, in place of whatever
     * was served before.
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public static function serve(array $entries, string $generatedAt = '2026-11-01T00:00:00Z', string $etag = '"v1"'): void
    {
        self::install();

        self::respond(Http::response(self::envelope(self::payload($entries, $generatedAt)), 200, ['ETag' => $etag]));
    }

    /**
     * Answers the feed's URL with this from now on. A fresh fake, since the
     * first stub registered for a URL keeps answering otherwise.
     */
    public static function respond(mixed $response): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([self::URL => $response]);
    }

    private static function keys(): void
    {
        if (is_null(self::$secret)) {
            $keypair = sodium_crypto_sign_keypair();
            self::$secret = sodium_crypto_sign_secretkey($keypair);
            self::$public = sodium_crypto_sign_publickey($keypair);
        }
    }
}
