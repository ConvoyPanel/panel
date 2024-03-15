<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'root_admin' => $user->root_admin,
        ];
    }
}
