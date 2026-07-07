<?php

namespace App\Http\Controllers\Admin;

use App\Data\PaginationMeta;
use App\Data\User\ApiKeyData;
use App\Enums\Api\ApiKeyType;
use App\Http\Requests\Admin\Tokens\StoreTokenRequest;
use App\Models\PersonalAccessToken;
use App\Services\Api\CreateApplicationTokenService;
use Illuminate\Http\Request;
use LogicException;
use Spatie\QueryBuilder\QueryBuilder;

class TokenController
{
    public function __construct(
        private CreateApplicationTokenService $createApplicationToken,
    ) {}

    public function index(Request $request)
    {
        $tokens = QueryBuilder::for(PersonalAccessToken::query())
            ->with('createdBy')
            ->defaultSort('-id')
            ->where('personal_access_tokens.type', ApiKeyType::APPLICATION->value)
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($tokens, ApiKeyData::class);
    }

    public function store(StoreTokenRequest $request)
    {
        $newToken = $this->createApplicationToken->handle($request->user(), $request->name, $request->abilities());

        if (! $newToken->accessToken instanceof PersonalAccessToken) {
            throw new LogicException('Sanctum is not using the application personal access token model.');
        }

        $newToken->accessToken->loadMissing('createdBy');

        return ApiKeyData::fromModel($newToken->accessToken, $newToken->plainTextToken);
    }

    public function destroy(PersonalAccessToken $token)
    {
        $token->delete();

        return response()->noContent();
    }
}
