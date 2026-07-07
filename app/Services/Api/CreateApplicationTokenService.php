<?php

namespace App\Services\Api;

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\SystemActor;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class CreateApplicationTokenService
{
    /**
     * Mint a panel-wide (application) token owned by the system actor — not the creating admin — so
     * it survives that admin's deletion. The minting admin is recorded in `created_by` for audit.
     *
     * @param  list<string>  $abilities
     */
    public function handle(User $creator, string $name, array $abilities = ['*']): NewAccessToken
    {
        $token = new PersonalAccessToken([
            'type' => ApiKeyType::APPLICATION,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'created_by' => $creator->getKey(),
        ]);

        $token->tokenable()->associate(SystemActor::instance());
        $token->save();

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }
}
