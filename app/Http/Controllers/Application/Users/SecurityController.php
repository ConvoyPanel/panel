<?php

namespace App\Http\Controllers\Application\Users;

use App\Http\Controllers\Controller;
use App\Models\SSOToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SecurityController extends Controller
{
    public function store(User $user)
    {
        $SSOToken = SSOToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(50))
        ]);

        return new Response([
            'data' => $SSOToken->token,
        ]);
    }
}
