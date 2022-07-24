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
        $this->node = clone $node;

        return $this;
    }

    public function instance()
    {
        //$this->proxmox()->nodes()->node($this->node->cluster)->tasks()->get(['']);
        return $this->proxmox()->nodes()->node($this->node->cluster)->qemu()->vmid($this->server->vmid);
    }

    public function nodeInstance()
    {
        return $this->proxmox()->nodes()->node($this->node->cluster);
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
            $this->node->auth_type,
            //true
        ];

        $proxmox = new PVE(...$node);

        return $proxmox;
    }

    public function removeDataProperty(mixed $data, bool $returnNullIfEmpty = false)
    {
        if (gettype($data) === 'array') {
            return $data['data'] ? $data['data'] : $data;
        } else {
            return $returnNullIfEmpty ? null : [];
        }
    }
}
