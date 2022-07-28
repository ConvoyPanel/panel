<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/servers/Index', [
            'servers' => Server::with(['template', 'owner:id,name,email', 'node:id,name'])->get(),
        ]);
    }

    public function show(Server $server)
    {
        return Inertia::render('admin/servers/Show', [
            'server' => $server,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/servers/Create');
    }

    public function search(Request $request)
    {
        return Server::search($request->search)->get()->load(['node:id,name']);
    }
}
