<?php

namespace App\Services\Servers;

use App\Enums\Network\AddressType;
use App\Exceptions\Service\Server\InvalidTemplateException;
use App\Jobs\Servers\ProcessBuild;
use App\Models\IPAddress;
use App\Models\Objects\Server\Limits\AddressLimitsObject;
use App\Models\Objects\Server\ServerDeploymentObject;
use App\Models\Server;
use App\Models\Template;
use App\Services\Activity\ActivityLogBatchService;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Proxmox\Api\Nodes\Node\Network;
use Webmozart\Assert\Assert;

/**
 * Class ServerCreationService
 * @package App\Services\Servers
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
