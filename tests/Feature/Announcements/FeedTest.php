<?php

use Convoy\Services\Announcements\AnnouncementFeed;
use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Announcements\InvalidFeedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeAnnouncementFeed;

beforeEach(function () {
    Cache::flush();
    FakeAnnouncementFeed::install();
    config(['convoy.releases.url' => '']);
});

function feed(): AnnouncementFeed
{
    return app(AnnouncementFeed::class);
}

it('stores a feed whose signature verifies', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()]);

    $this->artisan('announcements:check')->expectsOutputToContain('verified: 1 current')->assertSuccessful();

    expect(feed()->cached()['etag'])->toBe('"v1"')
        ->and(feed()->cached()['generated_at'])->toBe('2026-11-01T00:00:00Z')
        ->and(array_map(fn ($a) => $a->id, app(AnnouncementService::class)->all()))->toBe(['2026-10-21-php2shell']);
});

it('asks for the feed with nothing but its version, and only from the configured url', function () {
    FakeAnnouncementFeed::serve([]);

    feed()->refresh();

    Http::assertSent(fn (Request $request) => $request->url() === FakeAnnouncementFeed::URL
        && $request->method() === 'GET'
        && $request->hasHeader('User-Agent', 'Convoy/'.config('app.version'))
        && ! $request->hasHeader('Authorization')
        && ! $request->hasHeader('If-None-Match'));
});

it('verifies the decoded payload, not its base64 text', function () {
    $payload = FakeAnnouncementFeed::payload([FakeAnnouncementFeed::entry()]);
    $keypair = sodium_crypto_sign_keypair();
    config(['convoy.announcements.keys' => [FakeAnnouncementFeed::KEY_ID => base64_encode(sodium_crypto_sign_publickey($keypair))]]);

    $signedOverText = json_encode([
        'key_id' => FakeAnnouncementFeed::KEY_ID,
        'signature' => base64_encode(sodium_crypto_sign_detached(base64_encode($payload), sodium_crypto_sign_secretkey($keypair))),
        'payload' => base64_encode($payload),
    ]);

    expect(fn () => feed()->verify($signedOverText))->toThrow(InvalidFeedException::class, 'signature does not match')
        ->and(feed()->verify(FakeAnnouncementFeed::envelope($payload, secret: sodium_crypto_sign_secretkey($keypair)))['announcements'])
        ->toHaveCount(1);
});

it('refuses a tampered payload and keeps the last verified copy', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()]);
    feed()->refresh();

    // Signed for one announcement, then served with another swapped in.
    $envelope = json_decode(FakeAnnouncementFeed::envelope(FakeAnnouncementFeed::payload([FakeAnnouncementFeed::entry()], '2026-11-02T00:00:00Z')), true);
    $envelope['payload'] = base64_encode(FakeAnnouncementFeed::payload([
        FakeAnnouncementFeed::entry(['id' => 'fake', 'url' => 'https://github.com/ConvoyPanel/panel/discussions/1']),
    ], '2026-11-02T00:00:00Z'));

    FakeAnnouncementFeed::respond(Http::response(json_encode($envelope)));

    expect(feed()->refresh())->toBeFalse()
        ->and(feed()->lastError()['message'])->toContain('signature does not match')
        ->and(array_map(fn ($a) => $a->id, app(AnnouncementService::class)->all()))->toBe(['2026-10-21-php2shell']);
});

it('refuses a feed signed with a key it does not know', function () {
    $other = sodium_crypto_sign_keypair();
    $body = FakeAnnouncementFeed::envelope(
        FakeAnnouncementFeed::payload([FakeAnnouncementFeed::entry()]),
        keyId: '2027-01',
        secret: sodium_crypto_sign_secretkey($other),
    );

    expect(fn () => feed()->verify($body))->toThrow(InvalidFeedException::class, 'key "2027-01", which this panel doesn\'t know');

    // A known id with someone else's key fails the signature instead.
    $forged = FakeAnnouncementFeed::envelope(
        FakeAnnouncementFeed::payload([FakeAnnouncementFeed::entry()]),
        secret: sodium_crypto_sign_secretkey($other),
    );

    expect(fn () => feed()->verify($forged))->toThrow(InvalidFeedException::class, 'signature does not match');
});

it('refuses what is not a signed envelope', function (string $body) {
    expect(fn () => feed()->verify($body))->toThrow(InvalidFeedException::class);
})->with([
    'not json' => ['<html>502 Bad Gateway</html>'],
    'unsigned payload' => [json_encode(['payload' => base64_encode('{}')])],
    'signature not base64' => [json_encode(['key_id' => FakeAnnouncementFeed::KEY_ID, 'signature' => '!!', 'payload' => 'e30='])],
]);

it('refuses a signed payload that is not an announcements document', function () {
    FakeAnnouncementFeed::install();
    $body = FakeAnnouncementFeed::envelope(json_encode(['announcements' => 'nope']));

    expect(fn () => feed()->verify($body))->toThrow(InvalidFeedException::class, 'not an announcements document');
});

it('drops an announcement that links outside convoypanel.com and github.com/ConvoyPanel', function (string $url) {
    FakeAnnouncementFeed::serve([
        FakeAnnouncementFeed::entry(['id' => 'off-allowlist', 'url' => $url]),
        FakeAnnouncementFeed::entry(['id' => 'on-allowlist', 'url' => 'https://convoypanel.com/blog/5-0']),
    ]);

    feed()->refresh();

    expect(array_map(fn ($a) => $a->id, app(AnnouncementService::class)->all()))->toBe(['on-allowlist']);
})->with([
    'another host' => ['https://evil.example/convoy'],
    'a lookalike host' => ['https://convoypanel.com.evil.example/'],
    'another github org' => ['https://github.com/ConvoyPane1/panel'],
    'plain http' => ['http://convoypanel.com/blog'],
    'credentials' => ['https://convoypanel.com@evil.example/'],
    'javascript' => ['javascript:alert(1)'],
]);

it('takes links to anywhere else out of the body', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry([
        'body' => 'See [the fix](https://github.com/ConvoyPanel/panel/pull/1) or [this](https://evil.example/x).',
    ])]);

    feed()->refresh();

    expect(app(AnnouncementService::class)->all()[0]->body)
        ->toBe('See [the fix](https://github.com/ConvoyPanel/panel/pull/1) or this.');
});

it('drops an announcement that is malformed and keeps the rest', function (array $overrides) {
    FakeAnnouncementFeed::serve([
        FakeAnnouncementFeed::entry(['id' => 'malformed', ...$overrides]),
        FakeAnnouncementFeed::entry(['id' => 'fine']),
    ]);

    feed()->refresh();

    expect(array_map(fn ($a) => $a->id, app(AnnouncementService::class)->all()))->toBe(['fine']);
})->with([
    'unknown type' => [['type' => 'ad']],
    'security without severity' => [['severity' => null]],
    'id that is not a slug' => [['id' => '../etc']],
    'no title' => [['title' => '']],
    'bad range' => [['affects' => ['panel' => [['from' => 'latest']], 'php' => null]]],
    'bad date' => [['published_at' => 'yesterday-ish']],
]);

it('sends the ETag back and keeps its copy when the feed has not changed', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()], etag: '"abc"');
    feed()->refresh();

    $this->travel(1)->hours();
    FakeAnnouncementFeed::respond(Http::response('', 304));

    expect(feed()->refresh())->toBeTrue()
        ->and(feed()->cached()['checked_at'])->toBe(now()->utc()->toIso8601ZuluString())
        ->and(app(AnnouncementService::class)->all())->toHaveCount(1);

    Http::assertSent(fn (Request $request) => $request->hasHeader('If-None-Match', '"abc"'));
});

it('keeps the last verified copy when the console can not be reached', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()]);
    feed()->refresh();

    FakeAnnouncementFeed::respond(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $this->artisan('announcements:check')->expectsOutputToContain('could not be reached')->assertSuccessful();

    expect(app(AnnouncementService::class)->all())->toHaveCount(1);
});

it('refuses a copy older than the one it already verified', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()], generatedAt: '2026-11-05T00:00:00Z');
    feed()->refresh();

    FakeAnnouncementFeed::serve([], generatedAt: '2026-11-01T00:00:00Z');

    expect(feed()->refresh())->toBeFalse()
        ->and(app(AnnouncementService::class)->all())->toHaveCount(1);
});

it('forgets the copy after 7 days without a check that works', function () {
    // Redis expires keys on its own clock, which travel() doesn't move.
    config(['cache.default' => 'array']);

    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()]);
    feed()->refresh();

    $this->travel(8)->days();

    expect(feed()->cached())->toBeNull()
        ->and(app(AnnouncementService::class)->all())->toBe([]);
});

it('fetches nothing when CONVOY_ANNOUNCEMENTS_URL is empty', function () {
    Http::fake();
    config(['convoy.announcements.url' => '']);

    $this->artisan('announcements:check')->expectsOutputToContain('feed is off')->assertSuccessful();

    Http::assertNothingSent();
});

it('ships the console\'s public key', function () {
    $keys = require base_path('config/convoy.php');

    expect($keys['announcements']['keys'])->toHaveKey('2026-10')
        ->and(strlen(base64_decode($keys['announcements']['keys']['2026-10'], true)))->toBe(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
});
