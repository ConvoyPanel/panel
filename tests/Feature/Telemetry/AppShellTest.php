<?php

it('serves the app shell without a third-party analytics script', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertDontSee('plausible', escape: false)
        ->assertDontSee('beacon.performave.com', escape: false)
        ->assertDontSee('data-domain', escape: false);
});
