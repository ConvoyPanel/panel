<?php

namespace App\Http\Controllers\Client;

use App\Data\User\UserData;
use App\Services\Users\AccountPolicyResolver;
use Illuminate\Http\Request;

class SessionController
{
    public function __invoke(Request $request, AccountPolicyResolver $policy)
    {
        $user = $request->user();

        // The account screen renders straight off this payload, so the policy rides along with it
        // rather than costing the client a second request before it can decide what to show.
        return UserData::forSelf($user, $policy->for($user));
    }
}
