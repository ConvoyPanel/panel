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
    public function setServer(Server $server)
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
    public function setNode(Node $node)
    {
        $this->node = $node;

        return $this;
    }

    public function removeDataProperty(mixed $data, bool $returnNullIfEmpty = false)
    {
        if (gettype($data) === 'array') {
            return  $data['data'] ?? $data;
        } else {
            return $returnNullIfEmpty ? null : [];
        }
    }
}
