<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\SessionRecordData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\SessionRecord;
use App\Services\Auth\SessionRevocationService;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SessionRecordController
{
    public function __construct(
        private SessionRevocationService $revocation,
    ) {}

    public function index(Request $request)
    {
        $currentId = $request->session()->getId();
        $handler = $request->session()->getHandler();

        $records = SessionRecord::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_active_at')
            ->get();

        // Reconcile against the session store: Redis is the source of truth for what's actually
        // logged in, so drop (and delete) any row whose underlying session has expired or been
        // evicted. This keeps the list from ever showing a session that no longer exists, and
        // self-heals the metadata table on read.
        [$live, $stale] = $records->partition(
            fn (SessionRecord $record) => $record->session_id === $currentId
                || $handler->read($record->session_id) !== ''
        );

        if ($stale->isNotEmpty()) {
            SessionRecord::query()->whereKey($stale->modelKeys())->delete();
        }

        return SessionRecordData::collect(
            $live->map(fn (SessionRecord $record) => SessionRecordData::fromModel($record, $currentId)),
            DataCollection::class,
        );
    }

    public function destroy(Request $request, SessionRecord $sessionRecord)
    {
        // 404 (not 403) on someone else's session, so a row id can't be probed.
        if ($sessionRecord->user_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }

        // Revoking the current session is just a logout; leave that to the logout endpoint so the
        // response can also clear the cookie.
        if ($sessionRecord->session_id === $request->session()->getId()) {
            abort(422, 'You cannot revoke the session you are currently using; log out instead.');
        }

        // Kill the actual session in the store (Redis) so the other device is logged out, and drop
        // the metadata row — kept consistent by the shared revocation service.
        $ipAddress = $sessionRecord->ip_address;

        $this->revocation->revoke($sessionRecord);

        Audit::record(
            AuditEvent::ACCOUNT_SESSION_REVOKED,
            subject: $request->user(),
            properties: ['ip' => $ipAddress],
        );

        return response()->noContent();
    }
}
