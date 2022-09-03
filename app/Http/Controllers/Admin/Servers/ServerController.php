<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Models\Server;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\InstallService;
use App\Transformers\Application\ServerTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends Controller
{
    public function __construct(private InstallService $installService, private ServerCreationService $creationService)
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
                'address_ids' => Arr::get($data, 'addresses')
            ],
            'configuration' => [
                'boot_order' => ['default'],
                'disks' => [
                    [
                        'disk' => 'default',
                        'size' => Arr::get($data, 'disk'),
                    ]
                ],
                'template' => Arr::get($data, 'is_template', false),
                'visible' => Arr::get($data, 'is_visible', false),
            ],
        ];

        $server = $this->creationService->handle($deployment);

        return redirect()->route('admin.servers.show', [$server->id]);
    }

    public function destroy(Server $server, Request $request)
    {
        if (empty($request->no_purge))
        {
            try {
                $this->installService->setServer($server)->delete();
            } catch (Exception $e) {

            }
        }

        $server->delete();

        return redirect()->route('admin.servers');
    }

    public function search(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['name', 'user_id', 'node_id', 'vmid', 'installing'])
            ->allowedSorts(['id', 'user_id', 'node_id', 'vmid'])
            ->allowedIncludes(['node'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($servers, new ServerTransformer())->respond();
    }
}
