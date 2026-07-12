<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Status\NodeStatusData;
use App\Models\Node;
use App\Repositories\Proxmox\Node\ProxmoxStatusRepository;

class NodeStatusController
{
    public function __invoke(Node $node, ProxmoxStatusRepository $repository): NodeStatusData
    {
        return $repository->setNode($node)->getStatus();
    }
}
