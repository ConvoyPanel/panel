<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Service\Deployment\InvalidTemplateException;
use Convoy\Exceptions\Service\Server\Allocation\NoUniqueUuidComboException;
use Convoy\Exceptions\Service\Server\Allocation\NoUniqueVmidException;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Eloquent\ServerRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class ServerCreationService
 */
class ServerCreationService
{
    public function __construct(
        private NetworkService $networkService, private ServerRepository $repository,
        private ServerBuildDispatchService $buildDispatchService,
    ) {
    }

    public function handle(array $data)
    {
        $uuid = $this->generateUniqueUuidCombo();

        $shouldCreateServer = Arr::get($data, 'should_create_server');
        $template = $shouldCreateServer ? Template::where(
            'uuid', '=', Arr::get($data, 'template_uuid'),
        )->firstOrFail() : null;

        if ($template) {
            if ($template->group->node_id !== intval(Arr::get($data, 'node_id'))) {
                throw new InvalidTemplateException(
                    'This template is inaccessible to the specified node',
                );
            }
        }

        $nodeId = Arr::get($data, 'node_id');

        $server = Server::create([
            'uuid' => $uuid,
            'uuid_short' => substr($uuid, 0, 8),
            'status' => $shouldCreateServer ? Status::INSTALLING->value : null,
            'name' => Arr::get($data, 'name'),
            'user_id' => Arr::get($data, 'user_id'),
            'node_id' => $nodeId,
            'vmid' => Arr::get($data, 'vmid') ?? $this->generateUniqueVmId($nodeId),
            'hostname' => Arr::get($data, 'hostname'),
            'cpu' => Arr::get($data, 'limits.cpu'),
            'memory' => Arr::get($data, 'limits.memory'),
            'disk' => Arr::get($data, 'limits.disk'),
            'snapshot_limit' => Arr::get($data, 'limits.snapshots'),
            'backup_limit' => Arr::get($data, 'limits.backups'),
            'bandwidth_limit' => Arr::get($data, 'limits.bandwidth'),
        ]);

        $server->refresh();

        $deployment = ServerDeploymentData::from([
            'server' => $server,
            'template' => $template,
            'account_password' => Arr::get($data, 'account_password'),
            'should_create_server' => $shouldCreateServer,
            'start_on_completion' => Arr::get($data, 'start_on_completion'),
        ]);

        if ($addressIds = Arr::get($data, 'limits.address_ids')) {
            $this->networkService->updateAddresses($server, $addressIds);
        }

        $this->buildDispatchService->build($deployment);

        return $server;
    }

    public function generateUniqueVmId(int $nodeId): int
    {
        $vmid = random_int(100, 999999999);
        $attempts = 0;

        while (! $this->repository->isUniqueVmId($nodeId, $vmid)) {
            $vmid = random_int(100, 999999999);

            if ($attempts++ > 10) {
                throw new NoUniqueVmidException();
            }
        }

        return $vmid;
    }

    public function generateUniqueUuidCombo(): string
    {
        $uuid = Str::uuid()->toString();
        $short = substr($uuid, 0, 8);
        $attempts = 0;

        while (! $this->repository->isUniqueUuidCombo($uuid, $short)) {
            $uuid = Str::uuid()->toString();
            $short = substr($uuid, 0, 8);

            if ($attempts++ > 10) {
                throw new NoUniqueUuidComboException();
            }
        }

        return $uuid;
    }
}
