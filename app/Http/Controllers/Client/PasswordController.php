<?php

namespace App\Http\Controllers\Client;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdatePasswordRequest;
use App\Notifications\PasswordChanged;
use App\Services\Auth\SessionRevocationService;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function __construct(
        private SessionRevocationService $revocation,
    ) {}

    public function update(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        // Cycle the remember token in the same write as the password: a "remember me" cookie
        // authenticates on its own, so leaving it alone would let an evicted device walk straight
        // back in and make the eviction below cosmetic.
        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        // Changing a password is what someone does when they believe they are compromised, so it
        // has to evict the attacker rather than only change what a future login needs. Nothing
        // else does: `auth.session` is only on the SPA shell routes, so a stolen cookie driving
        // the client API alone is never checked against the new hash.
        $this->revocation->revokeOtherSessionsForUser($user, $request->session()->getId());

        Audit::record(AuditEvent::ACCOUNT_PASSWORD_UPDATED, subject: $user);

        $user->notify(new PasswordChanged($request->ip()));

        return response()->noContent();
    }
}
