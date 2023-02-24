<?php

namespace Convoy\Services\Nodes\Access;

use Convoy\Data\Node\Access\UserData;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\Node\ProxmoxAccessRepository;

class UserPruneService
{
    public function __construct(private ProxmoxAccessRepository $repository)
    {
    }

    public function handle(Node $node)
    {
        $users = $this->repository->setNode($node)->getUsers();

        $users = $users->filter(function (UserData $user) {
            return str_starts_with($user->id, 'convoy-') && $user->expires_at?->isPast();
        });

        $users->each(function (UserData $user) {
            $this->repository->deleteUser($user->id, $user->realm_type);
        });
    }
}
