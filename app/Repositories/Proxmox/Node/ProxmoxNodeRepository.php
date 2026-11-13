<?php

namespace Convoy\Repositories\Proxmox\Node;

use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Webmozart\Assert\Assert;

class ProxmoxNodeRepository extends ProxmoxRepository
{
    /**
     * The node's own status: `cpuinfo`, `memory`, `pveversion` and so on.
     *
     * @param  array  $options  Guzzle options for this request, such as a shorter timeout
     */
    public function getStatus(array $options = []): array
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient(options: $options)
            ->get("/api2/json/nodes/{$this->node->cluster}/status")
            ->json();

        return $this->getData($response);
    }
}
