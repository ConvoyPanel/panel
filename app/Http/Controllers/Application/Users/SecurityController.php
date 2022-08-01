<?php

namespace App\Http\Controllers\Application\Users;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\SSOToken;
use App\Models\User;
use Illuminate\Support\Str;

class SecurityController extends ApplicationApiController
{
    public function store(User $user)
    {
        $SSOToken = SSOToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(50))
        ]);

        return $this->returnContent([
            'data' => $SSOToken->token,
        ]);
    }
}
