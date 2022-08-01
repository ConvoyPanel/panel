<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Jobs\Servers\ProcessInstallation;
use App\Models\IPAddress;
use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\CreationService;
use App\Services\Servers\InstallService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function __construct(private InstallService $installService, private CreationService $creationService)
    {
    }

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

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->type, $request->validated(), $request->is_template, $request->is_visible);

        return redirect()->route('admin.servers.show', [$server->id]);
    }

    public function destroy(Server $server, Request $request)
    {
        if ($request->purge === true) {
            $this->installService->setServer($server)->delete();
        }

        $server->delete();

        return redirect()->route('admin.servers');
    }

    public function search(Request $request)
    {
        return Server::search($request->search)->get()->load(['node:id,name']);
    }
}
