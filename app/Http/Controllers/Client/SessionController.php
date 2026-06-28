<?php

namespace App\Http\Controllers\Client;

use App\Data\User\UserData;
use Illuminate\Http\Request;

class SessionController
{
    public function __invoke(Request $request)
    {
        return UserData::from($request->user());
    }
}
