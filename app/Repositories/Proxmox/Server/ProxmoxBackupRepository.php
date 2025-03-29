<?php

namespace App\Repositories\Proxmox\Server;

use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Models\Backup;
use App\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxBackupRepository extends ProxmoxRepository
{
    public function getBackups()
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $this->getNode()->backup_storage,
        ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/content', [
                'content' => 'backup',
                'vmid' => $this->getServer()->vmid,
            ])
            ->json();

        return $this->getData($response);
    }

    public function backup(BackupMode $mode, BackupCompressionType $compressionType)
    {
        $parsedMode = match ($mode) {
            BackupMode::KILL => 'stop',
            default => $mode->value,
        };

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/vzdump', [
                'vmid' => $this->getServer()->vmid,
                'storage' => $this->getNode()->backup_storage,
                'mode' => $parsedMode,
                'compress' => $compressionType === BackupCompressionType::NONE ? (int) false : $compressionType->value,
            ])
            ->json();

        return $this->getData($response);
    }

    public function restore(Backup $backup)
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu', [
                'vmid' => $this->getServer()->vmid,
                'force' => true,
                'archive' => "{$this->getNode()->backup_storage}:backup/{$backup->file_name}",
            ])
            ->json();

        return $this->getData($response);
    }

    public function delete(Backup $backup)
    {
        $node = $this->getNode();

        $response = $this->getHttpClientWithParams([
            'storage' => $node->backup_storage,
            'backup' => "{$node->backup_storage}:backup/{$backup->file_name}",
        ])
            ->delete('/api2/json/nodes/{node}/storage/{storage}/content/{backup}')
            ->json();

        return $this->getData($response);
    }
}
