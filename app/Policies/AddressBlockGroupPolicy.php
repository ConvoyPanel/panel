<?php

namespace App\Policies;

use App\Models\AddressBlockGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AddressBlockGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AddressBlockGroup $addressBlockGroup): Response
    {
        $isInUse = $addressBlockGroup->addressBlocks()
            ->whereHas('addresses', function ($query) {
                $query->whereNotNull('server_id');
            })
            ->exists();

        if ($isInUse) {
            return $this->deny('This address block group cannot be deleted because it contains IP addresses currently assigned to servers.');
        }

        return $this->allow();
    }
}
