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
        $this->revokeForUser($user);
    }

    /**
     * Revoke every session belonging to a user except the one making the request, so an action can
     * evict every other device without logging the actor out of the tab they are performing it in.
     */
    public function revokeOtherSessionsForUser(User $user, string $exceptSessionId): void
    {
        $this->revokeForUser($user, $exceptSessionId);
    }

    private function revokeForUser(User $user, ?string $exceptSessionId = null): void
    {
        SessionRecord::query()
            ->where('user_id', $user->getKey())
            ->when($exceptSessionId !== null, fn ($query) => $query->where('session_id', '!=', $exceptSessionId))
            ->get()
            ->each(fn (SessionRecord $record) => $this->revoke($record));
    }
}
