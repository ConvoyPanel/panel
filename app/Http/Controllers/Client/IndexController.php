<?php

namespace Convoy\Http\Controllers\Client;

use Convoy\Http\Controllers\ApplicationApiController;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndexController extends ApplicationApiController
{
    public function index(Request $request)
    {
        return Inertia::render('Dashboard', [
            'servers' => $request->user()->servers,
        ]);
    }

    public function verifyAuthState()
    {
        return $this->returnContent(['authenticated' => true]);
    }
}
