<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Service\Deployment\InvalidTemplateException;
use Convoy\Facades\Activity;
use Convoy\Facades\LogTarget;
use Convoy\Jobs\Servers\ProcessBuild;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Eloquent\ServerRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

/**
 * Class ServerCreationService
 */
class ServerCreationService extends ProxmoxService
{
    public function __construct(protected NetworkService $networkService, protected ServerRepository $repository)
    {
    }

    public function handle(array $data)
    {
        $uuid = $this->generateUniqueUuidCombo();

        $shouldCreateServer = Arr::get($data, 'create_server');
        $template = $shouldCreateServer ? Template::findOrFail(Arr::get($data, 'template_id')) : null;

        if ($template) {
            if ($template->server->node->id !== intval(Arr::get($data, 'node_id'))) {
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
            'vmid' => Arr::get($data, 'vmid', random_int(100, 999999999)),
            'hostname' => Arr::get($data, 'hostname'),
            'cpu' => Arr::get($data, 'limits.cpu'),
            'memory' => Arr::get($data, 'limits.memory'),
            'disk' => Arr::get($data, 'limits.disk'),
            'snapshot_limit' => Arr::get($data, 'limits.snapshots'),
            'backup_limit' => Arr::get($data, 'limits.backups'),
            'bandwidth_limit' => Arr::get($data, 'limits.bandwidth'),
        ]);

        $deployment = ServerDeploymentData::from([
            'server' => $server,
            'template' => $template,
            'should_create_server' => $shouldCreateServer,
            'start_after_completion' => Arr::get($data, 'start_after_completion')
        ]);

        $this->networkService->updateAddresses($server, Arr::get($data, 'address_ids'));

        // TODO: add the dispatch command

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

    public function createDeployment(Request $request): array
    {
        return [
            'type' => $request->input('type'),
            'user_id' => $request->input('user_id'),
            'node_id' => $request->input('node_id'),
            'template_id' => $request->input('template_id'),
            'name' => $request->input('name'),
            'vmid' => $request->input('vmid'),
            'limits' => [
                'cpu' => $request->input('limits.cpu'),
                'memory' => $request->input('limits.memory'),
                'address_ids' => $request->input('limits.addresses'),
                'disk' => $request->input('limits.disk'),
                'snapshot_limit' => $request->input('limits.snapshot_limit'),
                'backup_limit' => $request->input('limits.backup_limit'),
                'bandwidth_limit' => $request->input('limits.bandwidth_limit'),
            ],
            'config' => [
                'template' => $request->input('config.template'),
                'visible' => $request->input('config.visible'),
            ],
        ];
    }
}
