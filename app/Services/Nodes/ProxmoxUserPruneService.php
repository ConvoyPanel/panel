<?php

namespace Convoy\Services\Nodes;

use Convoy\Data\Node\Access\UserData;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\Node\ProxmoxAccessRepository;

class ProxmoxUserPruneService
{
    public function __construct(private ProxmoxAccessRepository $repository)
    {
    }

    public function handle(Node $node): void
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
