<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\AvatarCropData;
use App\Data\User\UserData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Client\DeleteAvatarRequest;
use App\Http\Requests\Client\UpdateAvatarRequest;
use App\Services\Users\AccountPolicyResolver;
use App\Services\Users\AvatarService;

class AvatarController
{
    public function __construct(
        private AvatarService $avatars,
        private AccountPolicyResolver $policy,
    ) {}

    public function store(UpdateAvatarRequest $request)
    {
        $user = $this->avatars->store(
            $request->user(),
            $request->file('avatar'),
            AvatarCropData::fromRequest($request),
        );

        Audit::record(AuditEvent::ACCOUNT_AVATAR_UPDATED, subject: $user);

        return UserData::forSelf($user, $this->policy->for($user));
    }

    public function destroy(DeleteAvatarRequest $request)
    {
        $user = $this->avatars->remove($request->user());

        Audit::record(AuditEvent::ACCOUNT_AVATAR_UPDATED, subject: $user);

        return UserData::forSelf($user, $this->policy->for($user));
    }
}
