<?php

namespace Database\Seeders;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Models\Deployment;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TmpInstallSeeder extends Seeder
{
    public function run(): void
    {
        $group = ImageGroup::firstOrCreate(
            ['name' => 'Ubuntu'],
            ['uuid' => (string) Str::uuid()],
        );
        $definition = ImageDefinition::firstOrCreate(
            ['name' => 'Ubuntu 24.04', 'image_group_id' => $group->id],
            ['uuid' => (string) Str::uuid(), 'ostype' => 'l26'],
        );
        $version = $definition->versions()->firstOrCreate(
            ['version' => '1.0.0'],
            [
                'uuid' => (string) Str::uuid(),
                'disks' => [[
                    'slot' => 'scsi0',
                    'role' => 'system',
                    'url' => 'https://example.invalid/ubuntu-24.04.qcow2',
                    'path' => null,
                    'sha256' => str_repeat('a', 64),
                    'size' => 600 * 1024 * 1024,
                    'virtual_size' => 8 * 1024 * 1024 * 1024,
                    'format' => 'qcow2',
                ]],
            ],
        );

        $server = Server::find(4);
        $server->update(['lifecycle' => ServerLifecycle::INSTALLING]);
        $server->deployments()->delete();

        $deployment = Deployment::create([
            'server_id' => $server->id,
            'image_definition_id' => $definition->id,
            'image_version_id' => $version->id,
            'type' => DeploymentType::INSTALL,
            'status' => DeploymentStatus::RUNNING,
            'start_on_completion' => true,
            'requested_at' => now(),
            'started_at' => now(),
        ]);

        $deployment->addSteps([
            ['name' => 'import', 'status' => DeploymentStatus::COMPLETED, 'progress_mode' => ProgressMode::DETERMINATE, 'progress_total' => 100, 'progress_current' => 100, 'started_at' => now(), 'completed_at' => now()],
            ['name' => 'configure', 'status' => DeploymentStatus::COMPLETED, 'progress_mode' => ProgressMode::INDETERMINATE, 'started_at' => now(), 'completed_at' => now()],
            ['name' => 'update-password', 'status' => DeploymentStatus::COMPLETED, 'progress_mode' => ProgressMode::INDETERMINATE, 'started_at' => now(), 'completed_at' => now()],
            ['name' => 'start-vm', 'status' => DeploymentStatus::RUNNING, 'progress_mode' => ProgressMode::INDETERMINATE, 'started_at' => now()],
        ]);

        echo "server uuid: {$server->uuid}\n";
    }
}
