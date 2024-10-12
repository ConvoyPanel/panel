<?php

namespace App\Http\Controllers\Client;

use App\Transformers\Client\UserTransformer;
use Illuminate\Http\Request;

class SessionController
{
    public function __invoke(Request $request)
    {
        return fractal($request->user(), new UserTransformer())->respond();
    }
}
