<?php

namespace App\Services\Nodes;

use App\Models\Node;
use App\Data\Node\Access\UserData;
use App\Repositories\Proxmox\Node\ProxmoxAccessRepository;

class UserPruneService
{
    public function __construct(private ProxmoxAccessRepository $repository)
    {
    }

    public function handle(Node $node)
    {
        $users = $this->repository->setNode($node)->getUsers();

        $users = $users->filter(function (UserData $user) {
            return str_starts_with($user->username, 'convoy-') && $user->expires_at?->isPast();
        });

        $users->each(function (UserData $user) {
            $this->repository->deleteUser($user->username, $user->realm_type);
        });
    }
}
