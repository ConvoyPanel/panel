<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class BackupService extends ProxmoxService
{
    public function getBackups()
    {
        $data = $this->nodeInstance()->storage()->zone('local')->content()->get(['content' => 'backup', 'vmid' => $this->server->vmid]);

        return $data ? $data['data'] : [];
    }

    // @param $mode snapshot | suspend | stop
    // @param $compression none | lzo | gzip | zstd
    public function createBackup(string $mode, string $compression)
    {
        return $this->nodeInstance()->vzdump()->post([
            'vmid' => $this->server->vmid,
            'storage' => 'local',
            'mode' => $mode,
            'remove' => 0,
            'compress' => $compression,
        ]);
    }

    public function rollback(string $archive)
    {
        return $this->nodeInstance()->qemu()->post([
            'vmid' => $this->server->vmid,
            'force' => 1,
            'archive' => $archive,
        ]);
    }

    public function deleteBackup(string $archive)
    {
        return $this->nodeInstance()->storage()->zone('local')->content()->volume($archive)->delete();
    }
}
