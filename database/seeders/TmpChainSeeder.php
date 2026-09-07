<?php

namespace Database\Seeders;

use App\Actions\Server\BuildServerAction;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerLifecycle;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use ReflectionMethod;

class TmpChainSeeder extends Seeder
{
    public function run(): void
    {
        $server = Server::find(4);
        $server->update(['lifecycle' => ServerLifecycle::INSTALLING]);
        $deployment = $server->deployments()->latest('id')->first();
        $deployment->update(['status' => DeploymentStatus::RUNNING]);

        $step = $deployment->steps()->where('name', 'start-vm')->first();

        $action = app(BuildServerAction::class);
        $call = fn (string $m, ...$a) => (new ReflectionMethod($action, $m))->invoke($action, ...$a);

        // Deliberately no real job in between: this isolates whether the chain's
        // trailing closure survives serialization through Redis and runs.
        $jobs = Arr::flatten([
            $call('onStart', $deployment),
            $call('onComplete', $deployment),
        ]);

        Bus::chain($jobs)->catch($call('onFail', $deployment))->dispatch();

        echo 'dispatched on connection: '.config('queue.default')."\n";
    }
}
