<?php

namespace App\Policies;

use App\Models\NetworkInterface;
use App\Models\User;

class NetworkInterfacePolicy
{
    public function delete(User $user, NetworkInterface $networkInterface): bool
    {
        return $user->root_admin;
    }
}

