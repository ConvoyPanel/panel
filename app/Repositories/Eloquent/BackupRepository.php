<?php

namespace App\Repositories\Eloquent;

use App\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupRepository
{
    public function getNonFailedBackups(Server $server): HasMany
    {
        return $server->backups()->where(function (Builder $query) {
            $query->whereNull('completed_at')
                  ->orWhereNull('errors');
        });
    }
}
