<?php

use Convoy\Models\User;
use Convoy\Services\Announcements\AnnouncementFeed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeAnnouncementFeed;

beforeEach(function () {
    Cache::flush();
    config(['app.version' => '5.0.0', 'convoy.releases.url' => '', 'convoy.telemetry.enabled' => false]);

    FakeAnnouncementFeed::serve([
        FakeAnnouncementFeed::entry(['id' => 'critical-for-us', 'severity' => 'critical', 'affects' => ['panel' => [['before' => '5.0.1']], 'php' => null]]),
        FakeAnnouncementFeed::entry(['id' => 'high-for-us', 'severity' => 'high', 'published_at' => '2026-10-30T00:00:00Z']),
        FakeAnnouncementFeed::entry(['id' => 'release-notes', 'type' => 'release', 'severity' => null, 'published_at' => '2026-10-29T00:00:00Z']),
        FakeAnnouncementFeed::entry(['id' => 'critical-for-others', 'severity' => 'critical', 'affects' => ['panel' => [['before' => '4.7.0']], 'php' => null]]),
        FakeAnnouncementFeed::entry(['id' => 'expired', 'severity' => 'high', 'expires_at' => '2026-11-01T00:00:00Z']),
    ]);
    app(AnnouncementFeed::class)->refresh();

    $this->admin = User::factory()->create(['root_admin' => true]);
});

it('puts critical announcements in the banner and the rest on the overview', function () {
    $this->actingAs($this->admin)->getJson('/api/admin/announcements')
        ->assertOk()
        ->assertJsonPath('data.banner.*.id', ['critical-for-us'])
        ->assertJsonPath('data.overview.*.id', ['high-for-us', 'release-notes'])
        ->assertJsonPath('data.banner.0.url', 'https://github.com/ConvoyPanel/panel/discussions/180');
});

it('shows announcements to admins only', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/admin/announcements')->assertForbidden();
    $this->getJson('/api/admin/announcements')->assertForbidden();
});

it('lets each admin dismiss an announcement for themselves', function () {
    $other = User::factory()->create(['root_admin' => true]);

    $this->actingAs($this->admin)->postJson('/api/admin/announcements/high-for-us/dismiss')->assertNoContent();
    // Dismissing twice is fine.
    $this->actingAs($this->admin)->postJson('/api/admin/announcements/high-for-us/dismiss')->assertNoContent();

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')
        ->assertJsonPath('data.overview.*.id', ['release-notes']);
    $this->actingAs($other)->getJson('/api/admin/announcements')
        ->assertJsonPath('data.overview.*.id', ['high-for-us', 'release-notes']);

    $this->assertDatabaseHas('announcement_dismissals', ['user_id' => $this->admin->id, 'announcement_id' => 'high-for-us']);
    $this->assertDatabaseCount('announcement_dismissals', 1);
});

it('does not let a critical announcement that applies be dismissed', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/announcements/critical-for-us/dismiss')
        ->assertUnprocessable();

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')
        ->assertJsonPath('data.banner.*.id', ['critical-for-us']);
    $this->assertDatabaseCount('announcement_dismissals', 0);
});

it('takes the banner down once the panel is past the affected versions', function () {
    config(['app.version' => '5.0.1']);

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')
        ->assertJsonPath('data.banner', []);
});

it('refuses an id that is not one', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/announcements/Not%20A%20Slug/dismiss')->assertNotFound();
});

it('lists every announcement on the Updates page with the reason it does or doesn\'t apply', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/announcements/high-for-us/dismiss');

    $response = $this->actingAs($this->admin)->getJson('/api/admin/settings/updates')
        ->assertOk()
        ->assertJsonPath('data.panel.version', '5.0.0')
        ->assertJsonPath('data.panel.from_source', false)
        ->assertJsonPath('data.feed.enabled', true)
        ->assertJsonPath('data.feed.error', null);

    $announcements = collect($response->json('data.announcements'))->keyBy('id');

    // Newest first; the three published at the same moment keep the feed's order.
    expect($announcements->keys()->all())->toBe(['high-for-us', 'release-notes', 'critical-for-us', 'critical-for-others', 'expired'])
        ->and($announcements['critical-for-us']['applies'])->toBeTrue()
        ->and($announcements['critical-for-us']['reasons'][0]['text'])->toBe('This panel runs Convoy 5.0.0, which is affected (before 5.0.1).')
        ->and($announcements['critical-for-others']['applies'])->toBeFalse()
        ->and($announcements['critical-for-others']['reasons'][0]['text'])->toBe('This panel runs Convoy 5.0.0. It is for Convoy before 4.7.0.')
        ->and($announcements['expired']['applies'])->toBeFalse()
        ->and($announcements['expired']['reasons'][0]['text'])->toBe('It expired on Nov 1, 2026.')
        ->and($announcements['high-for-us']['dismissed'])->toBeTrue();
});

it('checks for updates on request', function () {
    config(['convoy.releases.url' => 'https://api.github.test/repos/ConvoyPanel/panel/releases/latest']);
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry()], generatedAt: '2026-11-02T00:00:00Z');
    Http::fake(['api.github.test/*' => Http::response([
        'tag_name' => 'v5.0.2',
        'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v5.0.2',
        'published_at' => '2026-11-10T00:00:00Z',
    ])]);

    $this->actingAs($this->admin)->postJson('/api/admin/settings/updates/check')
        ->assertOk()
        ->assertJsonPath('data.release.latest.version', '5.0.2')
        ->assertJsonPath('data.release.update_available', true)
        ->assertJsonPath('data.feed.generated_at', '2026-11-02T00:00:00Z')
        ->assertJsonCount(1, 'data.announcements');

    $this->actingAs(User::factory()->create())->postJson('/api/admin/settings/updates/check')->assertForbidden();
});

it('can\'t say whether a build from source is behind', function () {
    config(['app.version' => 'canary', 'convoy.releases.url' => 'https://api.github.test/repos/ConvoyPanel/panel/releases/latest']);
    Http::fake(['api.github.test/*' => Http::response(['tag_name' => 'v5.0.2', 'html_url' => 'https://evil.example/'])]);

    $this->artisan('announcements:check')->assertSuccessful();

    $this->actingAs($this->admin)->getJson('/api/admin/settings/updates')
        ->assertJsonPath('data.panel.from_source', true)
        ->assertJsonPath('data.release.update_available', null)
        ->assertJsonPath('data.release.latest.url', 'https://github.com/ConvoyPanel/panel/releases/tag/v5.0.2');
});
