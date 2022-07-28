<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Jobs\Servers\ProcessInstallation;
use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\InstallService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function __construct(private InstallService $installService)
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
        if ($request->type === 'existing')
        {
            $server = Server::create($request->validated());

            return redirect()->route('admin.servers.show', [$server->id]);
        }

        if ($request->type === 'new')
        {
            $vmid = $request->vmid ? $request->vmid : random_int(1000,99999);
            $server = Server::create(array_merge($request->validated(), ['vmid' => $vmid]));

            ProcessInstallation::dispatch($request->template_id, $server->id, $server->node->cluster, $vmid);

            return redirect()->route('admin.servers.show', [$server->id]);
        }


    }

    public function search(Request $request)
    {
        return Server::search($request->search)->get()->load(['node:id,name']);
    }
}
