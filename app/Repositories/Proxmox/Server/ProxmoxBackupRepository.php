<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxBackupRepository extends ProxmoxRepository
{
    public $modes = [
        'snapshot',
        'suspend',
        'stop',
    ];

    public $compressionTypes = [
      'none',
      'lzo',
      'gzip',
      'zstd',
    ];

    public function getBackups(string $storage = 'local')
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/storage/%s/content', $this->node->cluster, $storage),[
                'query' => [
                    'content' => 'backup',
                    'vmid' => $this->server->vmid
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function backup(string $mode, string $compressionType)
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($mode, $this->modes, 'Invalid mode');
        Assert::inArray($compressionType, $this->compressionTypes, 'Invalid compression type');

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/vzdump', $this->node->cluster),[
                'json' => [
                    'vmid' => $this->server->vmid,
                    'storage' => $this->node->storage,
                    'mode' => $mode,
                    'remove' => 0,
                    'compress' => $compressionType,
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

    return $this->getData($response);
    }

    public function restore(string $archive)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu', $this->node->cluster),[
                'json' => [
                    'vmid' => $this->server->vmid,
                    'force' => 1,
                    'archive' => $archive,
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function delete(string $archive)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->delete(sprintf('/api2/json/nodes/%s/storage/%s/content/%s', $this->node->cluster, $this->node->storage, $archive));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }
    }
}