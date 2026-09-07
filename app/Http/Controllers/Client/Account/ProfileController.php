<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\UserData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Client\UpdateEmailRequest;
use App\Http\Requests\Client\UpdateProfileRequest;
use App\Services\Users\AccountPolicyResolver;

/**
 * The account's own name and email.
 *
 * Split across two endpoints because they are not equally dangerous. A display
 * name is cosmetic; an email address is where a password reset lands, so
 * changing it is gated on a confirmed identity in the route table -- the same
 * bar as minting an API key.
 */
class ProfileController
{
    public function __construct(
        private AccountPolicyResolver $policy,
    ) {}

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        if ($user->wasChanged()) {
            Audit::record(
                AuditEvent::ACCOUNT_PROFILE_UPDATED,
                subject: $user,
                properties: ['changed' => array_keys($user->getChanges())],
            );
        }

        return UserData::forSelf($user, $this->policy->for($user));
    }

    public function updateEmail(UpdateEmailRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        if ($user->wasChanged()) {
            Audit::record(
                AuditEvent::ACCOUNT_PROFILE_UPDATED,
                subject: $user,
                properties: ['changed' => array_keys($user->getChanges())],
            );
        }

        return UserData::forSelf($user, $this->policy->for($user));
    }
}
