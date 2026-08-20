<?php

namespace App\Listeners;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

/**
 * Authentication is the one area that cannot use explicit call sites: the controllers behind
 * login, logout and the two-factor challenge belong to Fortify, so there is nothing of ours to put
 * a `Audit::record()` in. Laravel's own auth events are the seam.
 *
 * The same applies to the two-factor endpoints, which are Fortify's as well.
 *
 * Everything else in the panel is audited explicitly at the call site — see docs/audit-log-plan.md.
 *
 * The methods are named `on*` rather than `handle*` on purpose: Laravel's event auto-discovery
 * claims any `handle*` method whose argument is an event, which would register every listener here
 * a second time on top of the explicit Event::subscribe() in AuditServiceProvider — and log every
 * sign-in twice.
 */
class AuditAuthenticationSubscriber
{
    public function onLogin(Login $event): void
    {
        Audit::record(
            AuditEvent::AUTH_LOGIN_SUCCEEDED,
            subject: $event->user instanceof User ? $event->user : null,
            properties: ['guard' => $event->guard, 'remember' => $event->remember],
            actor: $event->user instanceof User ? $event->user : null,
        );
    }

    public function onLogout(Logout $event): void
    {
        Audit::record(
            AuditEvent::AUTH_LOGOUT,
            subject: $event->user instanceof User ? $event->user : null,
            properties: ['guard' => $event->guard],
            actor: $event->user instanceof User ? $event->user : null,
        );
    }

    public function onFailed(Failed $event): void
    {
        // The actor is deliberately left null — nobody authenticated. The attempted identifier is
        // recorded instead, which is what makes a run of failures against one account legible.
        // Never the supplied password, even though $event->credentials carries it.
        Audit::record(
            AuditEvent::AUTH_LOGIN_FAILED,
            subject: $event->user instanceof User ? $event->user : null,
            properties: [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ],
        );
    }

    public function onTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->recordTwoFactor(AuditEvent::ACCOUNT_TWO_FACTOR_ENABLED, $event->user);
    }

    public function onTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->recordTwoFactor(AuditEvent::ACCOUNT_TWO_FACTOR_CONFIRMED, $event->user);
    }

    public function onTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->recordTwoFactor(AuditEvent::ACCOUNT_TWO_FACTOR_DISABLED, $event->user);
    }

    private function recordTwoFactor(AuditEvent $auditEvent, mixed $user): void
    {
        $user = $user instanceof User ? $user : null;

        // Subject is the account the factor belongs to; the actor falls back to whoever is
        // authenticated, so an admin disabling a user's two-factor is recorded as two different
        // people rather than as the user doing it to themselves.
        Audit::record($auditEvent, subject: $user);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            Failed::class => 'onFailed',
            TwoFactorAuthenticationEnabled::class => 'onTwoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'onTwoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'onTwoFactorDisabled',
        ];
    }
}
