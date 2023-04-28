<?php

namespace Convoy\Transformers\Application;

use Convoy\Models\SSOToken;
use League\Fractal\TransformerAbstract;

class SSOTokenTransformer extends TransformerAbstract
{
    public function transform(SSOToken $token)
    {
        return [
            'user_id' => $token->user_id,
            'token' => $token->token,
        ];
    }
}
