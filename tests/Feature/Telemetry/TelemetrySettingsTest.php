<?php

use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Models\User;
use Convoy\Services\Telemetry\PageViewCounter;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Settings\TelemetrySettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\TelemetryContract;

beforeEach(function () {
    Cache::flush();
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => 'https://console.test/api/v1/telemetry']);
    config(['convoy.announcements.url' => '']);

    $this->admin = User::factory()->create(['root_admin' => true]);
});

it('shows the switch to admins only', function () {
    $this->actingAs($this->admin)->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.disabled_by', null)
        ->assertJsonPath('data.report_time', app(TelemetryService::class)->reportTime());

    $this->actingAs(User::factory()->create())->getJson('/api/admin/settings/telemetry')->assertForbidden();
    $this->actingAs(User::factory()->create())->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertForbidden();
});

it('turns telemetry off from the settings screen, erasing the console\'s copy and replacing the id', function () {
    $before = app(TelemetryService::class)->installationId();

    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.enabled', false);

    expect(app(TelemetrySettings::class)->refresh()->installation_id)->not->toBe($before);
    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $before);

    // Saving the same value again erases nothing more and keeps the new id.
    $after = app(TelemetrySettings::class)->installation_id;

    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();

    Queue::assertPushed(EraseTelemetryJob::class, 1);
    expect(app(TelemetrySettings::class)->refresh()->installation_id)->toBe($after);

    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.enabled', true);
});

it('can not be switched on while the environment keeps it off', function () {
    config(['convoy.telemetry.enabled' => false]);
    app(TelemetrySettings::class)->fill(['enabled' => false])->save();

    $this->actingAs($this->admin)->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.disabled_by', 'CONVOY_TELEMETRY_ENABLED');

    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('enabled');
});

it('shows the exact report without sending it or resetting the counters', function () {
    Http::fake(['*' => Http::response(['data' => []])]);
    app(PageViewCounter::class)->record('/admin');

    $report = $this->actingAs($this->admin)->getJson('/api/admin/settings/telemetry/report')
        ->assertOk()
        ->json('data');

    expect($report['installation'])->toBe(app(TelemetryService::class)->installationId())
        ->and($report['views'])->toBe(['admin.overview' => 1])
        ->and(TelemetryContract::errors($report))->toBe([])
        ->and(app(PageViewCounter::class)->counts())->toBe(['admin.overview' => 1]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'console.test'));
});

it('shows each admin the notice that telemetry is on until they dismiss it', function () {
    $other = User::factory()->create(['root_admin' => true]);

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')->assertJsonPath('data.telemetry_notice', true);

    $this->actingAs($this->admin)->postJson('/api/admin/settings/telemetry/notice/dismiss')->assertNoContent();

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')->assertJsonPath('data.telemetry_notice', false);
    $this->actingAs($other)->getJson('/api/admin/announcements')->assertJsonPath('data.telemetry_notice', true);
});

it('drops the notice once telemetry is off', function () {
    app(TelemetryService::class)->disable();

    $this->actingAs($this->admin)->getJson('/api/admin/announcements')->assertJsonPath('data.telemetry_notice', false);
});
