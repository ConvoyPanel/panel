<?php

namespace App\Services\Users;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\User;
use App\Services\Auth\SessionRevocationService;
use App\Services\Mail\MailConfigurator;
use App\Settings\AccountSettings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Sends and redeems the "forgot password" link.
 *
 * Built on Laravel's password broker, which already hashes the stored token, expires it,
 * throttles repeat requests per account and runs both steps inside a timebox. What this adds is
 * the panel's policy: the operator's switch, the bar {@see AccountPolicyResolver} sets for
 * changing a password, and what a password change has to do here besides writing a hash.
 */
class PasswordResetService
{
    public function __construct(
        private AccountSettings $settings,
        private AccountPolicyResolver $policy,
        private MailConfigurator $mail,
        private SessionRevocationService $revocation,
        private UserInviteService $invites,
    ) {}

    /**
     * Whether the sign-in page offers a reset at all. Without a relay the link has nowhere to
     * go, and a form that always answers "check your email" would be lying to everyone.
     */
    public function isAvailable(): bool
    {
        return $this->settings->allow_password_reset && $this->mail->isConfigured();
    }

    /**
     * Email a reset link to whoever owns this address, if anyone does and they are allowed one.
     *
     * Returns nothing on purpose. The caller answers the same way for an unknown address, an
     * account the policy excludes and a request inside the broker's throttle window, so the form
     * cannot be used to find out which addresses have accounts.
     */
    public function sendLink(string $email, ?string $ip): void
    {
        // Through the broker's callback rather than a lookup beforehand, so the policy check runs
        // inside the same timebox as the token write and an ineligible account takes as long to
        // answer as an eligible one.
        $this->broker()->sendResetLink(['email' => $email], function (User $user, string $token) use ($ip) {
            if (! $this->policy->canResetPassword($user)) {
                // The broker writes the token before handing it over. Leaving it would also
                // start the per-account throttle for a link that was never sent.
                $this->broker()->deleteToken($user);

                return Password::INVALID_USER;
            }

            $user->sendPasswordResetNotification($token);

            Audit::record(
                AuditEvent::AUTH_PASSWORD_RESET_REQUESTED,
                subject: $user,
                properties: ['ip' => $ip],
            );

            return Password::RESET_LINK_SENT;
        });
    }

    /**
     * Whether a token still works for this address, so the reset screen can say a link is dead
     * before someone types a new password into it.
     */
    public function isValid(string $email, #[\SensitiveParameter] string $token): bool
    {
        $user = $this->broker()->getUser(['email' => $email]);

        return $user instanceof User
            && $this->policy->canResetPassword($user)
            && $this->broker()->tokenExists($user, $token);
    }

    /**
     * Set the new password and spend the token. Null when the link does not work.
     *
     * Does not sign anyone in. Holding the mailbox proves the address, not the account's second
     * factor, so the person goes through the sign-in page like any other time.
     */
    public function reset(
        string $email,
        #[\SensitiveParameter] string $token,
        #[\SensitiveParameter] string $password,
        ?string $ip,
    ): ?User {
        $user = $this->broker()->getUser(['email' => $email]);

        // Checked again rather than trusted from when the link was sent: the operator may have
        // turned resets off, or narrowed the policy, while the email sat in an inbox.
        if (! $user instanceof User || ! $this->policy->canResetPassword($user)) {
            return null;
        }

        $status = $this->broker()->reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user, string $password) {
                // The remember token cycles with the password for the same reason as on the
                // account screen: a "remember me" cookie signs in on its own.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return null;
        }

        // Every session, not every other one: there is no session of theirs making this request.
        // Someone resetting a password they forgot may be locking out whoever has it.
        $this->revocation->revokeAllForUser($user);

        // An outstanding invite is a second way to set this password. The person has just set
        // one, so the link in the older email should stop working.
        $this->invites->revoke($user);

        event(new PasswordReset($user));

        Audit::record(
            AuditEvent::AUTH_PASSWORD_RESET_COMPLETED,
            subject: $user,
            properties: ['ip' => $ip],
        );

        return $user;
    }

    /**
     * The concrete broker, not the contract: `getUser`, `tokenExists` and `deleteToken` are not on
     * the interface.
     */
    private function broker(): PasswordBroker
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();

        return $broker;
    }
}
