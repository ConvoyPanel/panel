<?php

namespace App\Transformers\Admin;

use Laravel\Sanctum\NewAccessToken;
use League\Fractal\TransformerAbstract;

class NewApiKeyTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
    ];

    protected array $defaultIncludes = [
        'user',
    ];

    public function transform(NewAccessToken $token)
    {
        return [
            'id' => $token->accessToken->id,
            'type' => $token->accessToken->type,
            'name' => $token->accessToken->name,
            'last_used_at' => $token->accessToken->last_used_at,
            'plain_text_token' => $token->plainTextToken,
        ];
    }

    public function includeUser(NewAccessToken $token)
    {
        return $this->item($token->accessToken->tokenable, new UserTransformer);
    }
}
