<?php

namespace Convoy\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Convoy\Enums\Api\ApiKeyType;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Models\PersonalAccessToken;
use Convoy\Transformers\Admin\ApiKeyTransformer;
use Convoy\Transformers\Admin\NewApiKeyTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Tokens\StoreTokenRequest;

class TokenController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $tokens = QueryBuilder::for(PersonalAccessToken::query())
            ->with('tokenable')
            ->where('personal_access_tokens.type', ApiKeyType::APPLICATION->value)
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($tokens, new ApiKeyTransformer())->respond();
    }

    public function store(StoreTokenRequest $request)
    {
        $token = $request->user()->createToken($request->name, ApiKeyType::APPLICATION);

        return fractal($token, new NewApiKeyTransformer())->respond();
    }

    public function destroy(PersonalAccessToken $token)
    {
        $token->delete();

        return $this->returnNoContent();
    }
}
