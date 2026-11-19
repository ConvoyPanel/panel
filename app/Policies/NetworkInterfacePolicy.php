<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\NetworkInterface;
use App\Models\SystemActor;
use App\Models\User;

class NetworkInterfacePolicy
{
    public function delete(User|SystemActor $user, NetworkInterface $networkInterface): bool
    {
        return $user instanceof SystemActor || $user->hasAdminPermission(AdminPermission::NODES_MANAGE);
    }
}
