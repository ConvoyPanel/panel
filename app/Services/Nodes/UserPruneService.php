<?php

namespace App\Services\Nodes;

use App\Data\Node\Access\UserData;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxAccessClient;

class UserPruneService
{
    public function __construct(private ProxmoxAccessClient $client) {}

    public function handle(Node $node): void
    {
        $users = $this->client->setNode($node)->getUsers();

        $users = $users->filter(function (UserData $user) {
            return str_starts_with($user->username, 'convoy-') && $user->expiresAt?->isPast();
        });

        $users->each(function (UserData $user) {
            $this->client->deleteUser($user->username, $user->realmType);
        });
    }
}
