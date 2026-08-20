<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\ApiKeyData;
use App\Enums\Api\ApiKeyType;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Client\Account\StoreApiKeyRequest;
use App\Models\PersonalAccessToken;
use App\Services\Api\CreateAccountTokenService;
use Illuminate\Http\Request;
use LogicException;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiKeyController
{
    public function __construct(
        private CreateAccountTokenService $createAccountToken,
    ) {}

    public function index(Request $request)
    {
        $tokens = PersonalAccessToken::query()
            ->where('type', ApiKeyType::ACCOUNT->value)
            ->whereMorphedTo('tokenable', $request->user())
            ->latest('id')
            ->get();

        return ApiKeyData::collect($tokens, DataCollection::class);
    }

    public function store(StoreApiKeyRequest $request)
    {
        $newToken = $this->createAccountToken->handle($request->user(), $request->name, $request->abilities());

        if (! $newToken->accessToken instanceof PersonalAccessToken) {
            throw new LogicException('Sanctum is not using the application personal access token model.');
        }

        Audit::record(
            AuditEvent::ACCOUNT_API_KEY_CREATED,
            subject: $newToken->accessToken,
            properties: [
                'name' => $newToken->accessToken->name,
                'abilities' => $newToken->accessToken->abilities,
            ],
        );

        return ApiKeyData::fromModel($newToken->accessToken, $newToken->plainTextToken);
    }

    public function destroy(Request $request, PersonalAccessToken $apiKey)
    {
        // 404 (not 403) on someone else's or a non-account token, so a token id can't be probed.
        if (
            $apiKey->type !== ApiKeyType::ACCOUNT
            || ! $apiKey->tokenable()->is($request->user())
        ) {
            throw new NotFoundHttpException;
        }

        $name = $apiKey->name;

        $apiKey->delete();

        // Subject is the acting user, not the token: the token row is gone, and this belongs in
        // the account's own security history.
        Audit::record(
            AuditEvent::ACCOUNT_API_KEY_DELETED,
            subject: $request->user(),
            properties: ['name' => $name],
        );

        return response()->noContent();
    }
}
