<?php

namespace Convoy\Repositories\Proxmox;

use Convoy\Models\Node;
use Convoy\Models\Server;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Contracts\Foundation\Application;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;
use GuzzleRetry\GuzzleRetryMiddleware;

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
    public function getData(ResponseInterface $response)
    {
        $json = json_decode($response->getBody(), true);

        return $json['data'] ?? $json;
    }

    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     */
    public function getHttpClient(array $headers = [], bool $authorize = true): Client
    {
        Assert::isInstanceOf($this->node, Node::class);

        return new Client([
            'verify' => $this->app->environment('production'),
            'base_uri' => "https://{$this->node->fqdn}:{$this->node->port}/",
            'timeout' => config('convoy.guzzle.timeout'),
            'connect_timeout' => config('convoy.guzzle.connect_timeout'),
            'handler' => $this->getHandlerStack(),
            'headers' => array_merge($headers, [
                'Authorization' => $authorize ? "PVEAPIToken={$this->node->token_id}={$this->node->secret}" : null,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => null,
            ]),
        ]);
    }

    public function getHandlerStack()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withoutHeader('User-Agent');
        }));
        $stack->push(GuzzleRetryMiddleware::factory([
            'max_retry_attempts' => 3,
            'retry_on_timeout' => true,
        ]));

        return $stack;
    }
}
