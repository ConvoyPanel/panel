<?php

namespace App\Services\Servers;

use App\Enums\Network\AddressType;
use App\Exceptions\Service\Server\InvalidTemplateException;
use App\Jobs\Servers\ProcessInstallation;
use App\Models\IPAddress;
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
        Assert::inArray(Arr::get($deployment, 'type'), ['existing', 'new']);

        if (Arr::get($deployment, 'type') === 'existing') {
            $server = Server::create([
                'name' => Arr::get($deployment, 'name'),
                'user_id' => Arr::get($deployment, 'user_id'),
                'node_id' => Arr::get($deployment, 'node_id'),
                'vmid' => Arr::get($deployment, 'vmid'),
            ]);

            if (Arr::get($deployment, 'config.template')) {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => Arr::get($deployment, 'config.visible', false)
                ]);
            }

            return $server;
        }

        if (Arr::get($deployment, 'type') === 'new') {
            $template = Template::findOrFail(Arr::get($deployment, 'template_id'));

            if ($template->server->node->id !== intval(Arr::get($deployment, 'node_id'))) {
                throw new InvalidTemplateException('This template is inaccessible to the specified node');
            }

            $addresses = $this->networkService->convertFromEloquent(Arr::get($deployment, 'limits.address_ids', []));

            $server = Server::create([
                'name' => Arr::get($deployment, 'name'),
                'user_id' => Arr::get($deployment, 'user_id'),
                'node_id' => Arr::get($deployment, 'node_id'),
                'vmid' => Arr::get($deployment, 'vmid') ?? random_int(100, 999999999),
            ]);

            if (Arr::get($deployment, 'limits.address_ids'))
                Arr::map(Arr::get($deployment, 'limits.address_ids'), function ($address_id) use ($server) {
                    IPAddress::find($address_id)->update(['server_id' => $server->id]);
                });

            $transformedDeployment = $deployment;
            $transformedDeployment['limits']['addresses'] = $addresses;

            $this->batch->transaction(function (string $uuid) use ($server, $transformedDeployment) {
                ProcessInstallation::dispatch($server, ServerDeploymentObject::from($transformedDeployment), $uuid);
            });

            return $server;
        }
    }
}
