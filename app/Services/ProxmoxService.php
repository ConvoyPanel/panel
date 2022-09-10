<?php

namespace App\Services;

use Proxmox\PVE;
use App\Models\Node;
use App\Models\Server;
use Webmozart\Assert\Assert;

/**
 * ProxmoxService class
 */
abstract class ProxmoxService
{
    protected Server $server;

    protected Node $node;


    /**
     * Set the server model this request is stemming from.
     *
     * @return $this
     */
    public function setServer(Server $server): self
    {
        $this->server = clone $server;

        $this->setNode($this->server->node);

        return $this;
    }

    /**
     * Set the node model this request is stemming from.
     *
     * @return $this
     */
    public function setNode(Node $node): self
    {
        $this->node = $node;

        return $this;
    }
}
