<?php

namespace App\Traits\Actions;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerLifecycle;
use App\Models\Deployment;
use Illuminate\Support\Facades\DB;

use function now;

trait ManagesDeploymentLifecycle
{
    private function onStart(Deployment $deployment): callable
    {
        return fn () => $deployment->update([
            'status' => DeploymentStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    private function onComplete(Deployment $deployment): callable
    {
        // The deployment's terminal status and the server lifecycle it implies are
        // one fact written to two rows. Committing them together keeps the UI's
        // two sources of truth from disagreeing if a write is interrupted.
        return function () use ($deployment) {
            DB::transaction(function () use ($deployment) {
                $deployment->update([
                    'status' => DeploymentStatus::COMPLETED,
                    'completed_at' => now(),
                ]);
                $deployment->server->update(['lifecycle' => ServerLifecycle::READY]);
            });
        };
    }

    private function onFail(Deployment $deployment, ServerLifecycle $serverStatus = ServerLifecycle::INSTALL_FAILED): callable
    {
        return function () use ($deployment, $serverStatus) {
            DB::transaction(function () use ($deployment, $serverStatus) {
                $deployment->update([
                    'status' => DeploymentStatus::FAILED,
                    'completed_at' => now(),
                ]);
                // `lifecycle`, not `status`: there is no `status` column on
                // servers, so this threw inside the chain's catch callback and
                // the failure was never recorded — the deployment stayed
                // running and the server sat in `installing` forever, with the
                // install screen up and no way off it but a rebuild.
                $deployment->server->update(['lifecycle' => $serverStatus]);
            });
        };
    }
}
