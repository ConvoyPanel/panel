<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Service\Deployment\InvalidTemplateException;
use Convoy\Facades\Activity;
use Convoy\Facades\LogTarget;
use Convoy\Jobs\Servers\ProcessBuild;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Eloquent\ServerRepository;
use Convoy\Services\Activity\ActivityLogBatchService;
use Convoy\Services\ProxmoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

/**
 * Class ServerCreationService
 */
class ServerCreationService extends ProxmoxService
{
    public function __construct(protected NetworkService $networkService, protected ServerRepository $repository, protected ActivityLogBatchService $batch)
    {
    }

    public function handle(ServerDeploymentObject $deployment)
    {
        Assert::inArray($deployment->type, ['existing', 'new']);

        $uuid = $this->generateUniqueUuidCombo();

        if ($deployment->type === 'existing') {
            $server = Server::create([
                'uuid' => $uuid,
                'uuidShort' => substr($uuid, 0, 8),
                'name' => $deployment->name,
                'user_id' => $deployment->user_id,
                'node_id' => $deployment->node_id,
                'vmid' => $deployment->vmid,
                'cpu' => $deployment->limits->cpu,
                'memory' => $deployment->limits->memory,
                'disk' => $deployment->limits->disk,
                'snapshot_limit' => $deployment->limits->snapshot_limit,
                'backup_limit' => $deployment->limits->backup_limit,
                'bandwidth_limit' => $deployment->limits->bandwidth_limit,
            ]);

            if ((bool) $deployment->config->template) {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => (bool) $deployment->config->visible,
                ]);
            }

            return $server;
        }

        if ($deployment->type === 'new') {
            $template = Template::findOrFail($deployment->template_id);

            if ($template->server->node->id !== intval($deployment->node_id)) {
                throw new InvalidTemplateException('This template is inaccessible to the specified node');
            }

            $server = Server::create([
                'uuid' => $uuid,
                'uuidShort' => substr($uuid, 0, 8),
                'name' => $deployment->name,
                'user_id' => $deployment->user_id,
                'node_id' => $deployment->node_id,
                'vmid' => $deployment->vmid ?? random_int(100, 999999999),
                'cpu' => $deployment->limits->cpu,
                'memory' => $deployment->limits->memory,
                'disk' => $deployment->limits->disk,
                'snapshot_limit' => $deployment->limits->snapshot_limit,
                'backup_limit' => $deployment->limits->backup_limit,
                'bandwidth_limit' => $deployment->limits->bandwidth_limit,
            ]);

            LogTarget::setSubject($server);

            $this->networkService->setServer($server)->updateAddresses($deployment->limits->address_ids ?? []);

            $server->update(['status' => Status::INSTALLING->value]);

            $this->batch->transaction(function (string $uuid) use ($server, $deployment) {
                $activity = Activity::event('server:build')->runner()->log();

                ProcessBuild::dispatch($server, $deployment->template_id, $uuid, $activity->id);
            });

            return $server;
        }
    }

    /**
     * Create a unique UUID and UUID-Short combo for a server.
     */
    private function generateUniqueUuidCombo(): string
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
