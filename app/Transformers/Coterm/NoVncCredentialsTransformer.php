<?php

namespace Convoy\Transformers\Coterm;

use Convoy\Data\Server\Proxmox\Console\NoVncCredentialsData;
use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class NoVncCredentialsTransformer extends TransformerAbstract
{
    /**
     * @param array{server: Server, credentials: NoVncCredentialsData} $data
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
