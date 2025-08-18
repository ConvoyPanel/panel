<?php

namespace App\Traits\Actions;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerStatus;
use App\Models\Deployment;
use function now;

trait ManagesDeploymentLifecycle
{
    private function onStart(Deployment $deployment): callable
    {
        return fn () => $deployment->update(['status' => DeploymentStatus::RUNNING]);
    }

    private function onComplete(Deployment $deployment): callable
    {
        return function () use ($deployment) {
            $deployment->update([
                'status' => DeploymentStatus::COMPLETED,
                'completed_at' => now(),
            ]);
            $deployment->server->update(['status' => ServerStatus::READY]);
        };
    }

    private function onFail(Deployment $deployment): callable
    {
        return function () use ($deployment) {
            $deployment->update([
                'status' => DeploymentStatus::FAILED,
                'completed_at' => now(),
            ]);
            $deployment->server->update(['status' => ServerStatus::INSTALL_FAILED]);
        };
    }
}
