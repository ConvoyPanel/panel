<?php

namespace App\Transformers\Coterm;

use App\Data\Server\Proxmox\Console\XTermCredentialsData;
use App\Models\Server;
use League\Fractal\TransformerAbstract;

class XTermCredentialsTransformer extends TransformerAbstract
{
    /**
     * @param array{server: Server, credentials: XTermCredentialsData} $data
     */
    public function transform(array $data): array
    {
        return [
            'node_fqdn' => $data['server']->node->fqdn,
            'node_port' => $data['server']->node->port,
            'node_pve_name' => $data['server']->node->cluster,
            'vmid' => $data['server']->vmid,
            ...$data['credentials']->toArray(),
        ];
    }
}
