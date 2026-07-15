<?php

namespace App\Repositories\Eloquent;

use App\Models\Backup;
use App\Models\Server;
use App\Support\ByteUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupRepository
{
    public function create(array $data): Backup
    {
        return Backup::create($data);
    }

    /**
     * @return HasMany<Backup, Server>
     */
    public function getNonFailedBackups(Server $server): HasMany
    {
        return $server->backups()->where(function (Builder $query) {
            $query->whereNull('completed_at')
                  ->orWhereNull('error_code');
        });
    }

    /**
     * Total bytes consumed by a server's non-failed backups.
     *
     * `backups.size` is persisted in MiB (StorageSizeCast) but read back as
     * bytes, and a SQL aggregate bypasses the cast entirely — so the sum has to
     * be scaled the same way the cast's read direction does.
     */
    public function getNonFailedBackupSize(Server $server): int
    {
        return ByteUnit::Mebibytes->toBytes(
            (int) $this->getNonFailedBackups($server)->sum('size'),
        );
    }

    /**
     * Backups created for a server within the last $period seconds (throttling).
     */
    /**
     * @return Builder<Backup>
     */
    public function getBackupsGeneratedDuringTimespan(int $serverId, int $period): Builder
    {
        return Backup::query()
            ->where('server_id', $serverId)
            ->where('created_at', '>=', now()->subSeconds($period));
    }
}
