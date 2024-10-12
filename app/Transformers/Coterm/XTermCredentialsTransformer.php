<?php

namespace Convoy\Transformers\Coterm;

use Convoy\Data\Server\Proxmox\Console\XTermCredentialsData;
use Convoy\Models\Server;
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
