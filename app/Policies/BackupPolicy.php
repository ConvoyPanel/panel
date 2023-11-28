<?php

namespace Convoy\Policies;

use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    public function before(User $user, string $ability, Backup|string $backup, Server $server): ?bool
    {
        if ($user->root_admin || $user->id === $server->user_id) {
            return true;
        }

        /*
         * Stop the user from accessing backups that are not associated with the
         * server they are trying to access.
         */
        if ($backup !== null && $backup->server_id !== $server->id) {
            return false;
        }

        return null;
    }

    /**
     * This is a horrendous hack to avoid Laravel's "smart" behavior that does
     * not call the before() function if there isn't a function matching the
     * policy permission.
     */
    public function __call(string $name, mixed $arguments)
    {
        // do nothing
    }
}
