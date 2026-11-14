<?php

use Carbon\CarbonImmutable;
use Convoy\Settings\TelemetrySettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// A panel imported from v4.7.0 arrives with v4's telemetry_settings table and
// none of 5.x's telemetry settings yet; this is the settings migration then.
function importV4Telemetry(?bool $enabled, ?string $reportedAt = null): string
{
    DB::table('settings')->where('group', 'telemetry')->delete();

    Schema::create('telemetry_settings', function (Blueprint $table) {
        $table->id();
        $table->boolean('enabled')->nullable();
        $table->uuid('installation_id');
        $table->timestamp('attempted_at')->nullable();
        $table->timestamp('reported_at')->nullable();
        $table->timestamps();
    });

    $id = (string) Str::uuid();
    DB::table('telemetry_settings')->insert([
        'id' => 1,
        'enabled' => $enabled,
        'installation_id' => $id,
        'reported_at' => $reportedAt,
        'created_at' => '2026-11-13 18:00:00',
        'updated_at' => '2026-11-13 18:00:00',
    ]);

    (require database_path('settings/2026_10_23_000000_create_telemetry_settings.php'))->up();

    return $id;
}

it('keeps the switch an admin turned off on v4.7.0, and the id', function () {
    $id = importV4Telemetry(false, '2026-11-14 03:21:00');

    $settings = app(TelemetrySettings::class)->refresh();

    expect($settings->enabled)->toBeFalse()
        ->and($settings->installation_id)->toBe($id)
        ->and($settings->reported_at?->toDateTimeString())->toBe('2026-11-14 03:21:00')
        ->and($settings->counting_since?->toDateTimeString())->toBe('2026-11-13 18:00:00')
        ->and(Schema::hasTable('telemetry_settings'))->toBeFalse();
});

it('reads v4.7.0\'s unset switch as its default, on', function () {
    $id = importV4Telemetry(null);

    $settings = app(TelemetrySettings::class)->refresh();

    expect($settings->enabled)->toBeTrue()
        ->and($settings->installation_id)->toBe($id)
        ->and($settings->reported_at)->toBeNull();
});

it('starts a fresh 5.x install on, with a new id', function () {
    DB::table('settings')->where('group', 'telemetry')->delete();
    CarbonImmutable::setTestNow('2026-11-15 09:00:00');

    (require database_path('settings/2026_10_23_000000_create_telemetry_settings.php'))->up();

    $settings = app(TelemetrySettings::class)->refresh();

    expect($settings->enabled)->toBeTrue()
        ->and(Str::isUuid($settings->installation_id))->toBeTrue()
        ->and($settings->counting_since?->toDateTimeString())->toBe('2026-11-15 09:00:00');

    CarbonImmutable::setTestNow();
});
