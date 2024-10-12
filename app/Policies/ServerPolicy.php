<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function before(User $user, string $ability, Server $server): ?bool
    {
        if ($user->root_admin || $user->id === $server->user_id) {
            return true;
        }

        return null;
    }

    public function view(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
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
