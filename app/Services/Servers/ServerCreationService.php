<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Exceptions\Service\Server\InvalidTemplateException;
use Convoy\Jobs\Servers\ProcessBuild;
use Convoy\Models\IPAddress;
use Convoy\Models\Objects\Server\Limits\AddressLimitsObject;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Activity\ActivityLogBatchService;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Proxmox\Api\Nodes\Node\Network;
use Webmozart\Assert\Assert;

/**
 * Class ServerCreationService
 * @package Convoy\Services\Servers
 */
class ServerCreationService extends ProxmoxService
{
    public function __construct(protected NetworkService $networkService, protected ActivityLogBatchService $batch)
    {
    }

    public function handle(ServerDeploymentObject $deployment)
    {
        Assert::inArray($deployment->type, ['existing', 'new']);

        if ($deployment->type === 'existing') {
            $server = Server::create([
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
                    'visible' => (bool) $deployment->config->visible
                ]);
            }

            return $server;
        }

        if ($deployment->type === 'new') {
            $template = Template::findOrFail($deployment->template_id);

            if ($template->server->node->id !== intval($deployment->node_id)) {
                throw new InvalidTemplateException('This template is inaccessible to the specified node');
            }

            $addresses = $this->networkService->convertFromEloquent($deployment->limits?->address_ids ?? []);

            $server = Server::create([
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

            if ($deployment->limits?->address_ids)
                Arr::map($deployment->limits->address_ids, function ($address_id) use ($server) {
                    IPAddress::find($address_id)->update(['server_id' => $server->id]);
                });

            $transformedDeployment = $deployment;
            $transformedDeployment->limits->addresses = AddressLimitsObject::from($addresses);

            $this->batch->transaction(function (string $uuid) use ($server, $transformedDeployment) {
                ProcessBuild::dispatch($server, ServerDeploymentObject::from($transformedDeployment), $uuid);
            });

            return $server;
        }
    }
}
