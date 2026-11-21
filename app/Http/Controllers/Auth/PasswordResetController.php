<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use App\Notifications\PasswordChanged;
use App\Services\Users\PasswordResetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordResetController
{
    public function __construct(private PasswordResetService $resets) {}

    /**
     * Ask for a reset link.
     *
     * Always 204 while resets are on. Whether the address has an account, is allowed a reset or
     * asked a minute ago all answer the same, so this cannot be used to find out who has one.
     */
    public function store(SendPasswordResetLinkRequest $request)
    {
        $this->ensureAvailable();

        $this->resets->sendLink($request->string('email')->toString(), $request->ip());

        return response()->noContent();
    }

    /** Whether a link still works, before anyone types a new password into it. */
    public function show(Request $request, string $token)
    {
        $this->ensureAvailable();

        if (! $this->resets->isValid((string) $request->query('email', ''), $token)) {
            throw new NotFoundHttpException('This reset link is no longer valid.');
        }

        return response()->noContent();
    }

    public function update(ResetPasswordRequest $request, string $token)
    {
        $this->ensureAvailable();

        $user = $this->resets->reset(
            $request->string('email')->toString(),
            $token,
            $request->string('password')->toString(),
            $request->ip(),
        );

        // Unknown address, wrong token and expired token are one answer, as with an invite.
        if ($user === null) {
            throw new NotFoundHttpException('This reset link is no longer valid.');
        }

        // The same tripwire as a change made from the account screen. If the reset was not
        // theirs, this is how they find out.
        $user->notify(new PasswordChanged($request->ip()));

        return response()->noContent();
    }

    /**
     * Off answers 404 rather than 403: with the switch off there is no reset endpoint as far as
     * a caller is concerned, the same as before the feature existed.
     */
    private function ensureAvailable(): void
    {
        if (! $this->resets->isAvailable()) {
            throw new NotFoundHttpException;
        }
    }
}
