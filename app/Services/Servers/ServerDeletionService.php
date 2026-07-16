<?php

namespace App\Services\Servers;

use App\Actions\Server\DeleteServerAction;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\ServerStatus;
use App\Exceptions\Http\Server\ServerStatusConflictException;
use App\Models\Server;

class ServerDeletionService
{
    public function __construct(private DeleteServerAction $deleteServerAction) {}

    public function handle(Server $server, bool $noPurge = false): void
    {
        $this->validateStatus($server);

        if ($noPurge) {
            $server->delete();

            return;
        }

        $deployment = $server->deployments()->create([
            'type' => DeploymentType::DELETE,
            'status' => DeploymentStatus::PENDING,
            'start_on_completion' => false,
            'requested_at' => now(),
        ]);

        $this->deleteServerAction->execute($deployment);
    }

    public function validateStatus(Server $server, bool $verifyStatusOnly = false): void
    {
        if ($server->status !== ServerStatus::DELETING) {
            throw new ServerStatusConflictException($server);
        }

        if (! $verifyStatusOnly) {
            if ($server->backups()->whereNull('completed_at')->exists()) {
                throw new ServerStatusConflictException($server);
            }
        }
    }
}
