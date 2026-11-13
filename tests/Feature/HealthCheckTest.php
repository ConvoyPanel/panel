<?php

use Illuminate\Support\Facades\Redis;

it('answers /up when the database and redis are reachable', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);

    $this->get('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'checks' => ['database' => true, 'redis' => true]])
        ->assertCookieMissing(config('session.cookie'));
});

// The SPA catch-all used to answer /up with a 200 whatever state the panel was in.
it('fails /up when redis is unreachable', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RedisException('Connection refused'));

    $this->get('/up')
        ->assertStatus(503)
        ->assertJsonPath('checks.redis', false)
        ->assertJsonPath('checks.database', true);
});

it('answers /up while the panel is in maintenance mode', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    $this->app->maintenanceMode()->activate([]);

    try {
        $this->get('/up')->assertOk();
        $this->get('/auth/login')->assertStatus(503);
    } finally {
        $this->app->maintenanceMode()->deactivate();
    }
});
