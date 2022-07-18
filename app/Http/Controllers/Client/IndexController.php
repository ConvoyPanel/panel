<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

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
