<?php

namespace App\Http\Controllers\Application\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Requests\Application\Servers\StoreServerRequest;
use App\Http\Requests\Application\Servers\UpdateServerRequest;
use App\Http\Requests\Application\Servers\UpdateDetailsRequest;
use App\Models\Objects\Server\ServerDeploymentObject;
use App\Models\Objects\Server\ServerSpecificationsObject;
use App\Models\Server;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\InstallService;
use App\Services\Servers\NetworkService;
use App\Services\Servers\ServerDetailService;
use App\Services\Servers\ServerUpdateService;
use App\Transformers\Application\ServerTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;
use Exception;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerCreationService $creationService, private NetworkService $networkService, private ServerDetailService $detailService, private ServerUpdateService $updateService, private InstallService $installService)
    {

    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['user_id', 'node_id', 'vmid', 'name', 'description', 'installing'])
            ->allowedSorts(['id', 'user_id', 'node_id', 'vmid'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($servers, new ServerTransformer())->respond();
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
                'cpu' => Arr::get($data, 'limits.cpu'),
                'memory' => Arr::get($data, 'limits.memory'),
                'address_ids' => Arr::get($data, 'limits.address_ids')
            ],
            'config' => [
                'boot_order' => ['default'],
                'disks' => [
                    [
                        'disk' => 'default',
                        'size' => Arr::get($data, 'limits.disk'),
                    ]
                ],
                'template' => Arr::get($data, 'config.template', false),
                'visible' => Arr::get($data, 'config.visible', false),
            ],
        ];

        $server = $this->creationService->handle(ServerDeploymentObject::from($deployment));

        return fractal($server, new ServerTransformer())->respond();
    }

    public function show(Server $server)
    {
        return fractal($server, new ServerTransformer())->respond();
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


        return $this->returnNoContent();
    }

    public function update(Server $server, UpdateServerRequest $request)
    {
        $server = $server->update($request->validated());

        return fractal($server, new ServerTransformer())->respond();
    }

    public function getDetails(Server $server)
    {
        return $this->detailService->setServer($server)->getDetails();
    }

    public function updateDetails(Server $server, UpdateDetailsRequest $request)
    {
        $data = $request->validated();

        $deployment = [
            'limits' => [
                'cpu' => Arr::get($data, 'limits.cpu'),
                'memory' => Arr::get($data, 'limits.memory'),
                'address_ids' => $this->networkService->convertFromEloquent(Arr::get($data, 'limits.addresses_ids', []))
            ],
            'config' => [
                'boot_order' => Arr::get($data, 'limits.disk') ? ['default'] : [],
                'disks' => [
                    [
                        'disk' => 'default',
                        'size' => Arr::get($data, 'limits.disk'),
                    ]
                ],
            ],
        ];

        $this->updateService->setServer($server)->handle(ServerSpecificationsObject::from($deployment));

        return $this->detailService->setServer($server)->getDetails();
    }
}
