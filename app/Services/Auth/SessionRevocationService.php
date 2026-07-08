<?php

namespace App\Services\Auth;

use App\Models\SessionRecord;
use App\Models\User;

/**
 * Revokes web sessions consistently across both stores: it destroys the underlying session in the
 * session store (Redis) *and* deletes its metadata row, so the two never drift. Shared by the
 * account "revoke session" endpoint and by user deletion (which would otherwise cascade-delete the
 * metadata rows while leaving live Redis sessions behind).
 */
class SessionRevocationService
{
    public function revoke(SessionRecord $record): void
    {
        app('session')->getHandler()->destroy($record->session_id);
        $record->delete();
    }

    /**
     * Revoke every session belonging to a user. Call before the user row is deleted, while the
     * metadata rows (and thus their session ids) still exist.
     */
    public function revokeAllForUser(User $user): void
    {
        SessionRecord::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->each(fn (SessionRecord $record) => $this->revoke($record));
    }
}
