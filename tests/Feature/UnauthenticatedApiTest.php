<?php

// A signed-out request without `Accept: application/json` -- a script, curl --
// used to be redirected to route('login'), which doesn't exist, and got a 500.
it('answers a signed-out API request with a 401 whatever it accepts', function (string $uri) {
    $this->get($uri, ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
})->with([
    'client' => '/api/client/servers',
    'admin' => '/api/admin/servers',
    'application' => '/api/application/servers',
]);
