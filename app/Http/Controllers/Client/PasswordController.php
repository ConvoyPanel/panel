<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UpdatePasswordRequest;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request)
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        return response()->noContent();
    }
}
