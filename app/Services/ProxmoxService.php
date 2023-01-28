<?php

namespace Convoy\Services;

use Convoy\Models\Node;
use Convoy\Models\Server;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * ProxmoxService class
 */
abstract class ProxmoxService
{
    protected Server $server;

    protected Node $node;

    protected bool $useCache = false;

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

    public function setUseCache(bool $shouldUseCache): self
    {
        $this->useCache = $shouldUseCache;

        return $this;
    }

    public function cache(string $key, Closure $callback, DateInterval|DateTimeInterface|int $ttl = 300)
    {
        if ($this->useCache) {
            return Cache::remember($key, $ttl, $callback);
        } else {
            return $callback();
        }
    }

    public function forget(string $key)
    {
        return Cache::forget($key);
    }
}
