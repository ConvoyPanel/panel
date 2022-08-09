<?php

namespace App\Services\Servers;

use App\Exceptions\Service\Server\InvalidTemplateException;
use App\Jobs\Servers\ProcessInstallation;
use App\Models\IPAddress;
use App\Models\Server;
use App\Models\Template;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Class ServerCreationService
 * @package App\Services\Servers
 */
class ServerCreationService extends ProxmoxService
{
    public function handle(array $deployment)
    {
        if(Arr::get($deployment, 'type') === 'existing')
        {
            $server = Server::create([
                'name' => Arr::get($deployment, 'name'),
                'user_id' => Arr::get($deployment, 'user_id'),
                'node_id' => Arr::get($deployment, 'node_id'),
                'vmid' => Arr::get($deployment, 'vmid'),
            ]);

            if (Arr::get($deployment, 'configuration.template'))
            {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => Arr::get($deployment, 'configuration.visible', false)
                ]);
            }

            return $server;
        }

        if(Arr::get($deployment, 'type') === 'new')
        {
            $template = Template::findOrFail(Arr::get($deployment, 'template_id'));

            if ($template->server->node->id !== intval(Arr::get($deployment, 'node_id')))
            {
                throw new InvalidTemplateException('This template is accessible to the specified node');
            }


        }

        /* if ($type === 'new') {
            // verify template is on the same node as the server being created
            $template = Template::findOrFail($serverData['template_id']);
            if ($template->server->node->id != $serverData['node_id']) {
                throw ValidationException::withMessages([
                    'template_id' => 'Template doesn\'t exist on selected node'
                ]);
            }

            $addresses = [
                'ip' => null,
                'ip6' => null
            ];

            // now time to fetch IPs
            if (isset($serverData['addresses'])) {
                foreach ($serverData['addresses'] as $_address) {
                    $address = IPAddress::find($_address);

                    if (isset($addresses[$address->type])) {
                        throw ValidationException::withMessages([
                            'addresses' => 'You cannot set multiple IPv4 or IPv6 addresses'
                        ]);
                    }

                    $addresses[$address->type] = [
                        'address' => $address->address,
                        'cidr' => $address->cidr,
                        'gateway' => $address->gateway,
                    ];
                }
            }

            $vmid = $serverData['vmid'] ?? random_int(1000, 999999999);
            $server = Server::create(array_merge($serverData, ['vmid' => $vmid]));

            // if nothing errors, we'll iterate again and this time set those IPs as locked
            if (isset($serverData['addresses'])) {
                foreach ($serverData['addresses'] as $_address) {
                    IPAddress::find($_address)->update(['server_id' => $server->id]);
                }
            }

            ProcessInstallation::dispatch($serverData['template_id'], $server->id, $server->node->cluster, $vmid, $addresses);

            return $server;
        }

        return []; */
    }
}
