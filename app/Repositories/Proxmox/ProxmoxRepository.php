<?php

namespace App\Repositories\Proxmox;

use GuzzleHttp\Client;
use App\Models\Node;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;
use App\Models\Server;
use Illuminate\Contracts\Foundation\Application;

abstract class ProxmoxRepository
{
    /**
     * @var Application
     */
    //protected $app;

    /**
     * @var Server|null
     */
    protected $server;

    /**
     * @var Node|null
     */
    protected $node;

    /**
     * BaseWingsRepository constructor.
     */
    //public function __construct(Application $application)
    //{
     //   $this->app = $application;
    //}

    /**
     * Set the server model this request is stemming from.
     *
     * @return $this
     */
    public function setServer(Server $server): static
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
    public function getHttpClient(array $headers = []): Client
    {
        Assert::isInstanceOf($this->node, Node::class);

        return new Client([
            'verify' => false,
            'base_uri' => "https://{$this->node->hostname}:{$this->node->port}/",
            'timeout' => config('convoy.guzzle.timeout'),
            'connect_timeout' => config('convoy.guzzle.connect_timeout'),
            'headers' => array_merge($headers, [
                'Authorization' => "PVEAPIToken={$this->node->token_id}={$this->node->secret}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        ]);
    }
}