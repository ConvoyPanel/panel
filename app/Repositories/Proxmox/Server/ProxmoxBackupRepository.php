<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Backup;
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

    public function getBackups()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/storage/%s/content', $this->node->cluster, $this->node->backup_storage), [
                'query' => [
                    'content' => 'backup',
                    'vmid' => $this->server->vmid,
                ],
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
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/vzdump', $this->node->cluster), [
                'json' => [
                    'vmid' => $this->server->vmid,
                    'storage' => $this->node->backup_storage,
                    'mode' => $mode,
                    'remove' => 0,
                    'compress' => $compressionType === 'none' ? 0 : $compressionType,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function restore(Backup $backup)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/qemu', $this->node->cluster), [
                'json' => [
                    'vmid' => $this->server->vmid,
                    'force' => 1,
                    'archive' => "{$this->node->backup_storage}:backup/{$backup->file_name}",
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function delete(Backup $backup)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->delete(sprintf('/api2/json/nodes/%s/storage/%s/content/%s', $this->node->cluster, $this->node->backup_storage, "{$this->node->backup_storage}:backup/{$backup->file_name}"));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}
