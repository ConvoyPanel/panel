<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'root_admin' => $user->root_admin,
            'servers_count' => (int) $user->servers_count,
        ];
    }
}
