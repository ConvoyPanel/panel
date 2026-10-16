<?php

namespace App\Services\Servers;

use App\Data\User\UserInviteData;
use App\Enums\User\UserType;
use App\Models\Server;
use App\Models\ServerSubuser;
use App\Models\User;
use App\Notifications\UserInvited;
use App\Services\Mail\MailConfigurator;
use App\Services\Users\UserInviteService;
use App\Settings\PermissionSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sharing a server with somebody else.
 *
 * Two shapes of invitee. One already has an account, in which case this is a row and nothing
 * more. The other does not, and Convoy has no public signup to send them to -- so the panel
 * creates a guest account and hands it the same invite link an admin-created account gets. That
 * flow is {@see UserInviteService}'s, reused rather than reinvented: one live invite per account,
 * a hash in the database, and a link that works even where SMTP does not.
 */
class SubuserService
{
    public function __construct(
        private ConnectionInterface $connection,
        private UserInviteService $invites,
        private MailConfigurator $mail,
        private PermissionSettings $settings,
    ) {}

    /**
     * Grant somebody access to a server.
     *
     * @param  list<string>  $permissions
     * @return array{subuser: ServerSubuser, invite: ?UserInviteData}
     */
    public function share(Server $server, User $actor, string $email, array $permissions): array
    {
        return $this->connection->transaction(function () use ($server, $actor, $email, $permissions) {
            $user = $this->resolve($email);
            $invite = null;

            if ($user === null) {
                [$user, $invite] = $this->createGuest($email);
            }

            $this->assertShareable($server, $user);

            $subuser = ServerSubuser::create([
                'server_id' => $server->id,
                'user_id' => $user->id,
                'permissions' => array_values(array_unique($permissions)),
                'created_by' => $actor->id,
            ]);

            return ['subuser' => $subuser, 'invite' => $invite];
        });
    }

    /**
     * Every grant on a server, dropped.
     *
     * Called when a server changes owner: the new owner did not invite these people, and
     * inheriting somebody else's guest list is not something they can be expected to audit.
     */
    public function revokeAll(Server $server): int
    {
        return $server->subusers()->delete();
    }

    private function resolve(string $email): ?User
    {
        // Case-insensitively: Fortify canonicalises usernames at login, so `Ada@x.test` and
        // `ada@x.test` are one account and sharing with the other spelling must find it rather
        // than colliding on the unique index.
        return User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->first();
    }

    /**
     * @return array{User, ?UserInviteData}
     */
    private function createGuest(string $email): array
    {
        if (! $this->settings->allow_guest_accounts) {
            throw ValidationException::withMessages([
                'email' => 'No account exists for that email address.',
            ]);
        }

        $user = User::create([
            'name' => Str::before($email, '@'),
            'email' => Str::lower($email),
            'type' => UserType::GUEST,
            // The account is unreachable until the invite is redeemed, which is the intended
            // state -- the same 64 random characters an admin-created invited account gets.
            'password' => Str::random(64),
        ]);

        return [$user, $this->invite($user)];
    }

    /**
     * Mint a link, email it where there is a relay to email it with, and hand it back either way.
     *
     * The link comes back even on success for the same reason it does on the admin side: mail is
     * not proof of delivery, and plenty of installs have no SMTP at all.
     */
    private function invite(User $user): UserInviteData
    {
        $token = $this->invites->issue($user);
        $ttl = (int) config('invites.ttl_days');
        $emailed = $this->mail->isConfigured();

        if ($emailed) {
            $user->notify(new UserInvited(UserInviteService::url($token), $ttl));
        }

        return new UserInviteData(
            link: UserInviteService::url($token),
            expiresAt: CarbonImmutable::now()->addDays($ttl),
            emailed: $emailed,
        );
    }

    private function assertShareable(Server $server, User $user): void
    {
        if ($user->id === $server->user_id) {
            throw ValidationException::withMessages([
                'email' => 'This account already owns the server.',
            ]);
        }

        if ($server->subusers()->where('user_id', '=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This server is already shared with that account.',
            ]);
        }
    }
}
