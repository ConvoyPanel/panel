<?php

namespace Convoy\Http\Controllers\Client;

use Convoy\Transformers\Client\UserTransformer;
use Illuminate\Http\Request;

class SessionController
{
    public function __invoke(Request $request)
    {
        return fractal($request->user(), new UserTransformer())->respond();
    }
}
