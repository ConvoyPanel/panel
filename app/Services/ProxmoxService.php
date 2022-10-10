<?php

namespace Convoy\Services;

use Convoy\Models\Node;
use Convoy\Models\Server;

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
