<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\UserData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Client\UpdateAvatarRequest;
use App\Services\Users\AvatarService;
use Illuminate\Http\Request;

class AvatarController
{
    public function __construct(
        private AvatarService $avatars,
    ) {}

    public function store(UpdateAvatarRequest $request)
    {
        $user = $this->avatars->store($request->user(), $request->file('avatar'));

        Audit::record(AuditEvent::ACCOUNT_AVATAR_UPDATED, subject: $user);

        return UserData::from($user);
    }

    public function destroy(Request $request)
    {
        $user = $this->avatars->remove($request->user());

        Audit::record(AuditEvent::ACCOUNT_AVATAR_UPDATED, subject: $user);

        return UserData::from($user);
    }
}
