<?php

namespace App\Services\Users;

use App\Models\User;
use App\Services\Auth\SessionRevocationService;

class UserDeletionService
{
    public function __construct(
        private SessionRevocationService $sessionRevocation,
    ) {}

    public function delete(User $user): void
    {
        $this->sessionRevocation->revokeAllForUser($user);
        $user->tokens()->delete();
        $user->delete();
    }
}
