<?php

namespace Convoy\Http\Controllers\Admin\Servers;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Servers\StoreServerRequest;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Server;
use Convoy\Services\Servers\BuildService;
use Convoy\Services\Servers\ServerCreationService;
use Convoy\Transformers\Admin\ServerTransformer as AdminServerTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends Controller
{
    public function __construct(private BuildService $buildService, private ServerCreationService $creationService)
    {
    }

    public function index(Request $request)
    {
        $servers = Server::with(['template', 'owner:id,name,email', 'node:id,name'])->paginate($request->query('per_page') ?? 50);

        return Inertia::render('admin/servers/Index', [
            'servers' => fractal($servers, new AdminServerTransformer())->toArray(),
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
        $data = $request->validated();

        $deployment = [
            'type' => Arr::get($data, 'type'),
            'user_id' => Arr::get($data, 'user_id'),
            'node_id' => Arr::get($data, 'node_id'),
            'template_id' => Arr::get($data, 'template_id'),
            'name' => Arr::get($data, 'name'),
            'vmid' => Arr::get($data, 'vmid'),
            'limits' => [
                'cpu' => Arr::get($data, 'cpu'),
                'memory' => Arr::get($data, 'memory'),
                'address_ids' => Arr::get($data, 'addresses'),
                'disk' => Arr::get($data, 'disk'),
                'snapshot_limit' => Arr::get($data, 'snapshot_limit'),
                'backup_limit' => Arr::get($data, 'backup_limit'),
                'bandwidth_limit' => Arr::get($data, 'bandwidth_limit'),
            ],
            'config' => [
                'template' => Arr::get($data, 'template', false),
                'visible' => Arr::get($data, 'visible', false),
            ],
        ];

        $server = $this->creationService->handle(ServerDeploymentObject::from($deployment));

        return redirect()->route('admin.servers.show', [$server->id]);
    }

    public function destroy(Server $server, Request $request)
    {
        if (empty($request->no_purge)) {
            try {
                $this->buildService->setServer($server)->delete();
            } catch (\Exception $e) {
            }
        }

        $server->delete();

        return redirect()->route('admin.servers');
    }

    public function search(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->with(['node', 'owner', 'template'])
            ->allowedFilters(['node.id', 'status', 'uuid', 'uuidShort', 'name', 'vmid'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($servers, new AdminServerTransformer())->respond();
    }
}
