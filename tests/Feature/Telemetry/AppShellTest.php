<?php

use Convoy\Models\TelemetrySetting;

it('serves the app shell without a third-party analytics script', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertDontSee('plausible', escape: false)
        ->assertDontSee('beacon.performave.com', escape: false)
        ->assertDontSee('data-domain', escape: false);
});

it('tells the browser whether to count page views', function (bool $enabled) {
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => 'https://console.test/api/v1/telemetry']);
    TelemetrySetting::current()->update(['enabled' => $enabled]);

    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertSee('"telemetry":'.($enabled ? 'true' : 'false'), escape: false);
})->with([true, false]);
