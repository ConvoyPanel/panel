<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\OAuthConnectionData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\OAuthConnection;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OAuthConnectionController
{
    /** The signed-in user's linked OAuth/OIDC identities. */
    public function index(Request $request)
    {
        $connections = OAuthConnection::query()
            ->where('user_id', '=', $request->user()->id)
            ->latest('created_at')
            ->get();

        return OAuthConnectionData::collect(
            $connections->map(fn (OAuthConnection $connection) => OAuthConnectionData::fromModel($connection)),
            DataCollection::class,
        );
    }

    /** Unlink a provider from the account. */
    public function destroy(Request $request, OAuthConnection $oauthConnection)
    {
        // 404 (not 403) on someone else's connection, so a row id can't be probed.
        if ($oauthConnection->user_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }

        $provider = $oauthConnection->provider;

        $oauthConnection->delete();

        Audit::record(
            AuditEvent::ACCOUNT_OAUTH_CONNECTION_DELETED,
            subject: $request->user(),
            properties: ['provider' => $provider],
        );

        return response()->noContent();
    }
}
