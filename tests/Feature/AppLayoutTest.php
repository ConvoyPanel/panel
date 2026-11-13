<?php

use Convoy\Models\User;

// Pages used to load Plausible's analytics script from beacon.performave.com.
it('loads no third-party analytics', function () {
    $html = $this->withoutVite()
        ->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('plausible')
        ->not->toContain('beacon.performave.com')
        ->not->toContain('hosted.convoypanel.com');
});
