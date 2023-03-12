<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Service\Deployment\InvalidTemplateException;
use Convoy\Facades\Activity;
use Convoy\Facades\LogTarget;
use Convoy\Jobs\Server\ProcessBuildJob;
use Convoy\Jobs\Servers\ProcessBuild;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Eloquent\ServerRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

/**
 * Class ServerCreationService
 */
class ServerCreationService
{
    public function __construct(protected NetworkService $networkService, protected ServerRepository $repository)
    {
    }

    public function handle(array $data)
    {
        $uuid = $this->generateUniqueUuidCombo();

        $shouldCreateServer = Arr::get($data, 'should_create_server');
        $template = $shouldCreateServer ? Template::where('uuid', '=', Arr::get($data, 'template_uuid'))->firstOrFail() : null;

        if ($template) {
            if ($template->group->node_id !== intval(Arr::get($data, 'node_id'))) {
                throw new InvalidTemplateException('This template is inaccessible to the specified node');
            }
        }

        $server = Server::create([
            'uuid' => $uuid,
            'uuid_short' => substr($uuid, 0, 8),
            'status' => $shouldCreateServer ? Status::INSTALLING->value : null,
            'name' => Arr::get($data, 'name'),
            'user_id' => Arr::get($data, 'user_id'),
            'node_id' => Arr::get($data, 'node_id'),
            'vmid' => Arr::get($data, 'vmid') ?? random_int(100, 999999999),
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
            'start_on_completion' => Arr::get($data, 'start_on_completion')
        ]);

        if ($addressIds = Arr::get($data, 'limits.address_ids')) {
            $this->networkService->updateAddresses($server, $addressIds);
        }

        ProcessBuildJob::dispatch($deployment);

        return $server;
    }

    /**
     * Create a unique UUID and UUID-Short combo for a server.
     */
    public function generateUniqueUuidCombo(): string
    {
        $uuid = Str::uuid()->toString();

        if (!$this->repository->isUniqueUuidCombo($uuid, substr($uuid, 0, 8))) {
            return $this->generateUniqueUuidCombo();
        }

        return $uuid;
    }
}
