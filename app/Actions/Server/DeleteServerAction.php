<?php

namespace App\Actions\Server;

use App\Enums\Server\DeploymentType;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\State;
use App\Jobs\Server\DeleteServerJob;
use App\Jobs\Server\MonitorStateJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\WaitUntilVmIsDeletedJob;
use App\Models\Deployment;
use App\Models\Server;

class DeleteServerAction
{
    public function execute(Server $server): array
    {
        return $this->getChainedDeleteJobs($server);
    }

    public function getChainedDeleteJobs(Server $server): array
    {
        $deployment = Deployment::create([
            'server_id' => $server->id,
            'type' => DeploymentType::DELETE,
        ]);

        $steps = $deployment->steps()->createMany([
            ['name' => 'kill'],
            ['name' => 'delete'],
        ]);

        return [
            new SendPowerCommandJob($steps[0], PowerCommand::KILL),
            new MonitorStateJob($steps[0], State::STOPPED),
            new DeleteServerJob($steps[1]),
            new WaitUntilVmIsDeletedJob($steps[1]),
        ];
    }
}
