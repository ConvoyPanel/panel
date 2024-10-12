<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Enums\Api\ApiKeyType;
use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\Tokens\StoreTokenRequest;
use Convoy\Models\PersonalAccessToken;
use Convoy\Transformers\Admin\ApiKeyTransformer;
use Convoy\Transformers\Admin\NewApiKeyTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TokenController extends ApiController
{
    public function index(Request $request)
    {
        $tokens = QueryBuilder::for(PersonalAccessToken::query())
                              ->with('tokenable')
                              ->defaultSort('-id')
                              ->where('personal_access_tokens.type', ApiKeyType::APPLICATION->value)
                              ->paginate(min($request->query('per_page', 50), 100))->appends(
                                  $request->query(),
                              );

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
