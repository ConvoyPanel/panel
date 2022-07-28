<?php

namespace App\Http\Controllers\Admin\Servers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Server $server)
    {
        return Inertia::render('admin/servers/settings/Index', [
            'server' => $server,
        ]);
    }
}
