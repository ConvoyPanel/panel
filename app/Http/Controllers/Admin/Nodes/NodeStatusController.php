<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Status\NodeStatusData;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxStatusClient;

class NodeStatusController
{
    public function __invoke(Node $node, ProxmoxStatusClient $client): NodeStatusData
    {
        return $client->setNode($node)->getStatus();
    }
}
