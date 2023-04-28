<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Enums\Server\BackupCompressionType;
use Convoy\Enums\Server\BackupMode;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Webmozart\Assert\Assert;

class ProxmoxBackupRepository extends ProxmoxRepository
{
    public function getBackups()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'storage' => $this->node->backup_storage,
            ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/content', [
                'content' => 'backup',
                'vmid' => $this->server->vmid,
            ])
            ->json();

        return $this->getData($response);
    }

    public function backup(BackupMode $mode, BackupCompressionType $compressionType)
    {
        Assert::isInstanceOf($this->server, Server::class);

        switch ($mode) {
            case BackupMode::KILL:
                $parsedMode = 'stop';
                break;
            default:
                $parsedMode = $mode->value;
                break;
        }

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
            ])
            ->post('/api2/json/nodes/{node}/vzdump', [
                'vmid' => $this->server->vmid,
                'storage' => $this->node->backup_storage,
                'mode' => $parsedMode,
                'compress' => $compressionType === BackupCompressionType::NONE ? false : $compressionType->value,
            ])
            ->json();

        return $this->getData($response);
    }

    public function restore(Backup $backup)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
            ])
            ->post('/api2/json/nodes/{node}/qemu', [
                'vmid' => $this->server->vmid,
                'force' => true,
                'archive' => "{$this->node->backup_storage}:backup/{$backup->file_name}",
            ])
            ->json();

        return $this->getData($response);
    }

    public function delete(Backup $backup)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'storage' => $this->node->backup_storage,
                'backup' => "{$this->node->backup_storage}:backup/{$backup->file_name}",
            ])
            ->delete('/api2/json/nodes/{node}/storage/{storage}/content/{backup}')
            ->json();

        return $this->getData($response);
    }
}
