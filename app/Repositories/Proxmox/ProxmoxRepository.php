<?php

namespace App\Repositories\Proxmox;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webmozart\Assert\Assert;

abstract class ProxmoxRepository
{
    protected Server $server;

    protected Node $node;

    public function setServer(Server $server): self
    {
        $this->server = $server;
        $this->node = $server->node;

        return $this;
    }

    public function setNode(Node $node): self
    {
        $this->node = $node;

        return $this;
    }

    protected function getServer(): Server
    {
        Assert::isInstanceOf(
            $this->server,
            Server::class,
            'Server is not set or invalid.'
        );

        return $this->server;
    }

    protected function getNode(): Node
    {
        Assert::isInstanceOf(
            $this->node,
            Node::class,
            'Node is not set or invalid.'
        );

        return $this->node;
    }

    public function getData(array|string $response): mixed
    {
        return $response['data'] ?? $response;
    }

    public function getHttpClient(
        bool $shouldAuthorize = true,
    ): PendingRequest {
        $client = Http::withOptions([
            'verify' => $this->getNode()->verify_tls,
            'timeout' => config('convoy.guzzle.timeout'),
            'connect_timeout' => config('convoy.guzzle.connect_timeout'),
        ])
            ->baseUrl("https://{$this->node->fqdn}:{$this->node->port}/")
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        if ($shouldAuthorize) {
            $client->withHeaders([
                'Authorization' => "PVEAPIToken={$this->node->token_id}={$this->node->token_secret}",
            ]);
        }

        return $client->throw(function (Response $response) {
            throw new RequestException($response);
        });
    }

    public function getHttpClientWithParams(
        array $params = [],
        bool $shouldAuthorize = true,
    ): PendingRequest {
        if (isset($this->node)) {
            $params['node'] = $this->node->name;
        }

        if (isset($this->server)) {
            $params['server'] = $this->server->vmid;
        }

        return $this->getHttpClient($shouldAuthorize)
            ->withUrlParameters($params);
    }
}
