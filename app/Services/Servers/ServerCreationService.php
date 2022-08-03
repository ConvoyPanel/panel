<?php

namespace App\Services\Servers;

use App\Jobs\Servers\ProcessInstallation;
use App\Models\IPAddress;
use App\Models\Server;
use App\Models\Template;
use App\Services\ProxmoxService;
use Illuminate\Validation\ValidationException;

/**
 * Class CreationService
 * @package App\Services\Servers
 */
class ServerCreationService extends ProxmoxService
{
    public function handle(string $type, array $serverData, ?bool $isTemplate = false, ?bool $isTemplateVisible = false)
    {
        if ($type === 'existing') {
            $server = Server::create($serverData);

            if ($isTemplate === true) {
                Template::create([
                    'server_id' => $server->id,
                    'visible' => $isTemplateVisible ? $isTemplateVisible : false
                ]);
            }

            return $server;
        }

        if ($type === 'new') {
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

        return [];
    }
}
