<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\NetworkInterface;
use App\Models\User;

class NetworkInterfacePolicy
{
    public function delete(User $user, NetworkInterface $networkInterface): bool
    {
        return $user->hasAdminPermission(AdminPermission::NODES_MANAGE);
    }
}
