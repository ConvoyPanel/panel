<?php

namespace App\Services\Proxmox;

use App\Exceptions\Proxmox\RequestException;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webmozart\Assert\Assert;

abstract class ProxmoxClient
{
    protected ?Server $server = null;

    protected Node $node;

    public function setServer(Server $server): static
    {
        $this->server = $server;
        $this->node = $server->node;

        return $this;
    }

    public function setNode(Node $node): static
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
        if (! isset($this->node)) {
            throw new \LogicException('Node is not set.');
        }

        return $this->node;
    }

    public function getData(array|string $response): mixed
    {
        return $response['data'] ?? $response;
    }

    /**
     * Get a pre-configured HTTP client for Proxmox API requests.
     *
     * Note: Operations performed with the returned client are configured to throw
     * a RequestException on HTTP request failures.
     *
     * @noinspection PhpDocRedundantThrowsInspection PhpStorm might flag this as redundant because the method itself doesn't throw.
     * @throws RequestException
     */
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

    /**
     * @throws RequestException
     */
    public function getHttpClientWithParams(
        array $params = [],
        bool $shouldAuthorize = true,
    ): PendingRequest {
        if (filled($this->node)) {
            $params['node'] = $this->node->name;
        }

        if (filled($this->server)) {
            $params['server'] = $this->server->vmid;
        }

        return $this->getHttpClient($shouldAuthorize)
            ->withUrlParameters($params);
    }
}
