<?php

namespace App\Services\Users;

use App\Models\User;
use App\Models\UserInvite;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Issues and redeems the links that let a new account set its own first password.
 *
 * This exists so the panel never has to email a credential. An admin creating an account no
 * longer picks a password on someone else's behalf — they hand over a link, and the person on
 * the other end chooses something the panel has never seen and no mailbox has ever held.
 */
class UserInviteService
{
    /**
     * Issue a fresh invite, replacing any that is already outstanding.
     *
     * Returns the plaintext token, which is the only time it exists in a readable form: the row
     * keeps its hash. Re-issuing deliberately invalidates the previous link — "resend" has to
     * mean the old one stops working, or revoking is impossible once a link has been forwarded.
     */
    public function issue(User $user): string
    {
        $token = Str::random(48);

        UserInvite::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => self::hash($token),
                'expires_at' => CarbonImmutable::now()->addDays(config('invites.ttl_days')),
            ],
        );

        return $token;
    }

    /**
     * The invite a token addresses, or null when it is unknown, spent or lapsed.
     *
     * All three answer identically on purpose: distinguishing them would confirm to whoever is
     * holding a guessed token that it once meant something.
     */
    public function resolve(string $token): ?UserInvite
    {
        return UserInvite::query()
            ->unexpired()
            ->with('user')
            ->where('token', '=', self::hash($token))
            ->first();
    }

    /**
     * Set the account's password and burn the invite.
     *
     * Deleting rather than marking consumed: a spent invite carries no information worth
     * keeping — the audit log records that it was accepted — and a row that cannot be redeemed
     * is one more thing to reason about on every lookup.
     */
    public function accept(UserInvite $invite, string $password): User
    {
        $user = $invite->user;

        $user->update(['password' => $password]);

        $invite->delete();

        return $user;
    }

    public function revoke(User $user): void
    {
        UserInvite::query()->where('user_id', '=', $user->id)->delete();
    }

    /**
     * The link an invite is handed over as.
     *
     * Built against APP_URL rather than the current request so a link minted by an API call
     * from a billing extension points at the panel rather than at whatever host that call
     * arrived on.
     */
    public static function url(string $token): string
    {
        return rtrim((string) config('app.url'), '/').'/auth/invite/'.$token;
    }

    /**
     * Unsalted sha256, matching how Sanctum stores personal access tokens: the input is 48
     * random characters, so there is no dictionary for a salt to defend against, and the lookup
     * has to be a single indexed equality check.
     */
    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
