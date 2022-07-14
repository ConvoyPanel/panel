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
    protected $server;

    protected $node;


    /**
     * Set the server model this request is stemming from.
     *
     * @return $this
     */
    public function setServer(Server $server)
    {
        $this->server = $server;

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

    public function instance()
    {
        return $this->proxmox()->nodes()->node($this->node->cluster)->qemu()->vmid($this->server->vmid);
    }

    public function mainInstance()
    {
        return $this->proxmox();
    }

    public function proxmox()
    {
        Assert::isInstanceOf($this->node, Node::class);

        $node = [
            $this->node->hostname,
            $this->node->username,
            $this->node->password,
            intval($this->node->port),
            $this->node->auth_type
        ];

        $proxmox = new PVE(...$node);

        return $proxmox;
    }
}