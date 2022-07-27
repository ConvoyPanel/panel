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
        return Inertia::render('admin/servers/Index');
    }

    public function search(Request $request)
    {
        return Server::search($request->search)->get()->load(['node:id,name']);
    }
}
