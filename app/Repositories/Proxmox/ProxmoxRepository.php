<?php

namespace App\Repositories\Proxmox;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webmozart\Assert\Assert;

abstract class ProxmoxRepository
{
    protected Server $server;

    protected Node $node;

    /**
     * BaseWingsRepository constructor.
     */
    public function __construct(private Application $app)
    {
    }

    /**
     * Set the server model this request is stemming from.
     *
     * @return $this
     */
    public function setServer(Server $server): static
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
    public function setNode(Node $node): static
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Removes the extra data property from the Proxmox API response
     *
     * @return mixed
     */
    public function getData(array|string $response): mixed
    {
        return $response['data'] ?? $response;
    }

    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     */
    public function getHttpClient(
        array $headers = [],
        array $options = [],
        bool $shouldAuthorize = true,
    ): PendingRequest {
        Assert::isInstanceOf($this->node, Node::class);

        return Http::withOptions(array_merge([
            'verify' => $this->node->verify_tls,
            'base_uri' => "https://{$this->node->fqdn}:{$this->node->port}/",
            'timeout' => config('convoy.guzzle.timeout'),
            'connect_timeout' => config('convoy.guzzle.connect_timeout'),
            'headers' => array_merge([
                'Authorization' => $shouldAuthorize ? "PVEAPIToken={$this->node->token_id}={$this->node->secret}" : null,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => null,
            ], $headers),
        ], $options))->throw(function (Response $response, RequestException $exception) {
            throw new ProxmoxConnectionException($response, $exception);
        });
    }
}
