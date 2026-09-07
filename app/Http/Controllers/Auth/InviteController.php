<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Auth\AcceptInviteRequest;
use App\Services\Users\UserInviteService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InviteController
{
    public function __construct(private UserInviteService $invites) {}

    /**
     * What the invite screen needs to address the person by name before they have signed in.
     *
     * Deliberately thin: a name and the address the account will sign in with, nothing about the
     * panel's contents. An unauthenticated caller holding a guessed token learns only what they
     * would already know from the email that carried it.
     */
    public function show(string $token)
    {
        $invite = $this->invites->resolve($token);

        if ($invite === null) {
            // Unknown, spent and expired are one answer. Telling them apart would confirm that a
            // guessed token once meant something.
            throw new NotFoundHttpException('This invitation is no longer valid.');
        }

        return response()->json([
            'data' => [
                'name' => $invite->user->name,
                'email' => $invite->user->email,
                'expiresAt' => $invite->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Set the password and sign them in.
     *
     * Signing in immediately is the point of arriving here: the alternative is bouncing someone
     * who has just proved they hold the invite to a login form to retype what they typed a
     * second ago.
     */
    public function store(AcceptInviteRequest $request, string $token)
    {
        $invite = $this->invites->resolve($token);

        if ($invite === null) {
            throw new NotFoundHttpException('This invitation is no longer valid.');
        }

        $user = $this->invites->accept($invite, $request->string('password')->toString());

        Auth::login($user);

        $request->session()->regenerate();

        // Recorded against the account itself: this is the moment it becomes usable, and the
        // actor is the account's own owner rather than the admin who created it.
        Audit::record(
            AuditEvent::AUTH_INVITE_ACCEPTED,
            subject: $user,
            properties: ['ip' => $request->ip()],
        );

        return response()->noContent();
    }
}
