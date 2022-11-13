<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Models\Backup;
use Convoy\Models\Server;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupRepository extends EloquentRepository
{
    public function model()
    {
        return Backup::class;
    }

    public function getNonFailedBackups(Server $server): HasMany
    {
        return $server->backups()->where(function ($query) {
            $query->whereNull('completed_at')
                ->orWhere('successful', true);
        });
    }
}