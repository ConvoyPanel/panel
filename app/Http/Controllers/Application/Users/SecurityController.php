<?php

namespace Convoy\Http\Controllers\Application\Users;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\SSOToken;
use Convoy\Models\User;
use Illuminate\Support\Str;

class SecurityController extends ApplicationApiController
{
    public function store(User $user)
    {
        $SSOToken = SSOToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(50)),
        ]);

        return $SSOToken->toArray();
    }
}
