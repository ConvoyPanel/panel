<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\SessionRecordData;
use App\Models\SessionRecord;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SessionRecordController
{
    public function index(Request $request)
    {
        $currentId = $request->session()->getId();

        $records = SessionRecord::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_active_at')
            ->get();

        return SessionRecordData::collect(
            $records->map(fn (SessionRecord $record) => SessionRecordData::fromModel($record, $currentId)),
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

        // Kill the actual session in the store (Redis) so the other device is logged out, then drop
        // the metadata row.
        $request->session()->getHandler()->destroy($sessionRecord->session_id);
        $sessionRecord->delete();

        return response()->noContent();
    }
}
