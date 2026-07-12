<?php

use App\Jobs\Server\SyncServerRateLimitJob;
use App\Models\Location;
use App\Models\Node;
use Illuminate\Support\Facades\Bus;

it('fans out a per-server rate-limit job for each server on a node', function () {
    Bus::fake();

    [, , , $server] = createServerModel();

    $this->artisan('servers:sync-rate-limits')->assertSuccessful();

    Bus::assertBatched(fn ($batch) => collect($batch->jobs)->flatten()
        ->contains(fn ($job) => $job instanceof SyncServerRateLimitJob
            && $job->server->is($server)));
});

it('queues nothing for a node with no servers', function () {
    Bus::fake();

    Node::factory()->for(Location::factory())->create();

    $this->artisan('servers:sync-rate-limits')->assertSuccessful();

    Bus::assertNothingBatched();
});
