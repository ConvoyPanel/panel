<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Backup\BackupData;
use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Backup;
use App\Models\Storage;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class ProxmoxBackupClient extends ProxmoxClient
{
    /**
     * @return Collection<int, BackupData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getBackups(Storage $storage): Collection
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $storage->name,
        ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/content', [
                'content' => 'backup',
                'vmid' => $this->getServer()->vmid,
            ])
            ->json();

        return BackupData::collect(
            array_map(fn(array $backup) => BackupData::fromRaw($backup), $this->getData($response)),
            Collection::class
        );
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return string UPID
     */
    public function backup(BackupMode $mode, BackupCompressionType $compressionType, string $storage): string
    {
        $parsedMode = match ($mode) {
            BackupMode::KILL => 'stop',
            default => $mode->value,
        };

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/vzdump', [
                'vmid' => $this->getServer()->vmid,
                'storage' => $storage,
                'mode' => $parsedMode,
                'compress' => $compressionType === BackupCompressionType::NONE ? (int) false : $compressionType->value,
            ])
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return string UPID
     */
    public function restore(Backup $backup): string
    {
        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu', [
                'vmid' => $this->getServer()->vmid,
                'force' => true,
                'archive' => "{$backup->storage->name}:backup/{$backup->file_name}",
            ])
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return string UPID
     */
    public function delete(Backup $backup): string
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $backup->storage->name,
            'backup' => "{$backup->storage->name}:backup/{$backup->file_name}",
        ])
            ->delete('/api2/json/nodes/{node}/storage/{storage}/content/{backup}')
            ->json();

        return $this->getData($response);
    }
}
