<?php

namespace App\Services\Api;

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class CreateAccountTokenService
{
    /**
     * Mint an end-user personal access token bound to the user themselves. Unlike an application
     * token (owned by the system actor, with the minting admin recorded in `created_by`), an
     * account token *is* owned by its user, so no separate audit link is needed.
     *
     * @param  list<string>  $abilities
     */
    public function handle(User $user, string $name, array $abilities = ['*']): NewAccessToken
    {
        $token = new PersonalAccessToken([
            'type' => ApiKeyType::ACCOUNT,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
        ]);

        $token->tokenable()->associate($user);
        $token->save();

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }
}
