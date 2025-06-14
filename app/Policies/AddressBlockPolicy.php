<?php

namespace App\Policies;

use App\Models\AddressBlock;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AddressBlockPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, AddressBlock $addressBlock): Response
    {
        $isInUse = $addressBlock->addresses()
            ->whereNotNull('server_id')
            ->exists();

        if ($isInUse) {
            return $this->deny('This address block cannot be deleted because it contains IP addresses currently assigned to servers.');
        }

        return $this->allow();
    }
}
