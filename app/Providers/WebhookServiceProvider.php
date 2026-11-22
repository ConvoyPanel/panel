<?php

namespace App\Providers;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Models\Backup;
use App\Models\Deployment;
use App\Services\Webhooks\WebhookDispatcher;
use App\Support\Webhooks\WebhookEvents;
use Illuminate\Support\ServiceProvider;

/**
 * The webhook events that are not audit entries: work that finishes after the request that
 * started it. See {@see WebhookEvents::lifecycle()}.
 */
class WebhookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Deployment::updated(function (Deployment $deployment) {
            if (! $deployment->wasChanged('status')) {
                return;
            }

            $event = match ([$deployment->type, $deployment->status]) {
                [DeploymentType::INSTALL, DeploymentStatus::COMPLETED],
                [DeploymentType::REINSTALL, DeploymentStatus::COMPLETED],
                [DeploymentType::IMPORT, DeploymentStatus::COMPLETED] => WebhookEvents::SERVER_INSTALLATION_COMPLETED,
                [DeploymentType::INSTALL, DeploymentStatus::FAILED],
                [DeploymentType::REINSTALL, DeploymentStatus::FAILED],
                [DeploymentType::IMPORT, DeploymentStatus::FAILED] => WebhookEvents::SERVER_INSTALLATION_FAILED,
                [DeploymentType::MIGRATE, DeploymentStatus::COMPLETED] => WebhookEvents::SERVER_MIGRATION_COMPLETED,
                [DeploymentType::MIGRATE, DeploymentStatus::FAILED] => WebhookEvents::SERVER_MIGRATION_FAILED,
                default => null,
            };

            if ($event === null) {
                return;
            }

            app(WebhookDispatcher::class)->dispatch($event, [
                'server' => WebhookDispatcher::server($deployment->server),
                'deployment' => [
                    'id' => $deployment->id,
                    'type' => $deployment->type->value,
                    'status' => $deployment->status->value,
                ],
            ]);
        });

        Backup::updated(function (Backup $backup) {
            // A backup is finished once, when completed_at is first set; error_code says how.
            if (! $backup->wasChanged('completed_at') || $backup->getOriginal('completed_at') !== null) {
                return;
            }

            app(WebhookDispatcher::class)->dispatch(
                $backup->error_code === null ? WebhookEvents::SERVER_BACKUP_COMPLETED : WebhookEvents::SERVER_BACKUP_FAILED,
                [
                    'server' => WebhookDispatcher::server($backup->server),
                    'backup' => [
                        'uuid' => $backup->uuid,
                        'name' => $backup->name,
                        'size' => $backup->size,
                        'errorCode' => $backup->error_code?->value,
                        'errorMessage' => $backup->error_message,
                    ],
                ],
            );
        });
    }
}
