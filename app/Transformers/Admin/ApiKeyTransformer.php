<?php

namespace App\Transformers\Admin;

use App\Models\PersonalAccessToken;
use League\Fractal\TransformerAbstract;

class ApiKeyTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
    ];

    protected array $defaultIncludes = [
        'user',
    ];

    public function transform(PersonalAccessToken $token)
    {
        return [
            'id' => $token->id,
            'type' => $token->type,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at,
        ];
    }

    public function includeUser(PersonalAccessToken $token)
    {
        return $this->item($token->tokenable, new UserTransformer);
    }
}
