<?php

namespace Convoy\Policies;

use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }

    public function update(User $user, Backup $backup, Server $server): bool
    {
        if ($backup->server_id !== $server->id) {
            return false;
        }

        return $user->id === $server->user_id;
    }

    public function delete(User $user, Backup $backup, Server $server): bool
    {
        if ($backup->server_id !== $server->id) {
            return false;
        }

        return $user->id === $server->user_id;
    }

    public function restore(User $user, Backup $backup, Server $server): bool
    {
        if ($backup->server_id !== $server->id) {
            return false;
        }

        return $user->id === $server->user_id;
    }

}
