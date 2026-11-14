<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Under /api because routes/base.php sends every other path to the SPA, which
// would answer this route instead. Checked with assertContent: an error page
// prints the request's headers and server variables, so assertSee passed even
// when the route never ran.
beforeEach(function () {
    Route::get('/api/_test/client-ip', fn (Request $request) => $request->ip());
});

afterEach(function () {
    Request::setTrustedProxies([], -1);
});

it('ignores forwarded headers when no proxy is trusted', function () {
    config(['trustedproxy.proxies' => null]);

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('/api/_test/client-ip')
        ->assertOk()
        ->assertContent('10.0.0.5');
});

it('reads the client address from a trusted proxy', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8, 192.0.2.1']);

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('/api/_test/client-ip')
        ->assertOk()
        ->assertContent('203.0.113.9');
});
