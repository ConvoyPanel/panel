<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Models\ISO;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Illuminate\Http\Request;
use Convoy\Enums\Server\Status;
use Convoy\Models\TemplateGroup;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Enums\Server\AuthenticationType;
use Illuminate\Database\ConnectionInterface;
use Convoy\Services\Servers\CloudinitService;
use Convoy\Services\Servers\AllocationService;
use Convoy\Services\Servers\ServerAuthService;
use Convoy\Data\Server\Proxmox\Config\DiskData;
use Convoy\Transformers\Client\MediaTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Services\Servers\ServerBuildDispatchService;
use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Transformers\Client\RenamedServerTransformer;
use Convoy\Transformers\Client\ServerNetworkTransformer;
use Convoy\Transformers\Client\TemplateGroupTransformer;
use Convoy\Transformers\Client\ServerSecurityTransformer;
use Convoy\Transformers\Client\ServerBootOrderTransformer;
use Convoy\Http\Requests\Client\Servers\Settings\MountMediaRequest;
use Convoy\Http\Requests\Client\Servers\Settings\RenameServerRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateNetworkRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateSecurityRequest;
use Convoy\Http\Requests\Client\Servers\Settings\ReinstallServerRequest;
use Convoy\Http\Requests\Client\Servers\Settings\UpdateBootOrderRequest;

class SettingsController extends ApplicationApiController
{
    public function __construct(private ServerAuthService $authService, private ConnectionInterface $connection, private CloudinitService $cloudinitService, private ServerBuildDispatchService $buildDispatchService, private AllocationService $allocationService)
    {
    }

    public function rename(RenameServerRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $this->cloudinitService->updateHostname($server, $request->hostname);

            $server->update($request->validated());
        });

        return fractal($server, new RenamedServerTransformer)->respond();
    }

    public function getTemplateGroups(Request $request, Server $server)
    {
        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->defaultSort('order_column')
            ->allowedFilters(['name']);

        if (! $request->user()->root_admin) {
            $templateGroups = $templateGroups->where([['template_groups.hidden', '=', false], ['template_groups.node_id', '=', $server->node->id]])
                ->with(['templates' => function ($query) {
                    $query->where('hidden', '=', false)->orderBy('order_column');
                }])->get();
        } else {
            $templateGroups = $templateGroups->where('template_groups.node_id', '=', $server->node->id)
                ->with(['templates' => function ($query) {
                    $query->orderBy('order_column');
                }])->get();
        }

        return fractal($templateGroups, new TemplateGroupTransformer)->respond();
    }

    public function reinstall(ReinstallServerRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $server->update(['status' => Status::INSTALLING->value]);

            $deployment = ServerDeploymentData::from([
                'server' => $server,
                'template' => Template::where('uuid', '=', $request->template_uuid)->firstOrFail(),
                'account_password' => $request->account_password,
                'should_create_server' => true,
                'start_on_completion' => $request->boolean('start_on_completion'),
            ]);

            $this->buildDispatchService->rebuild($deployment);
        });

        return $this->returnNoContent();
    }

    public function getBootOrder(Server $server)
    {
        $availableDevices = $this->allocationService->getDisks($server);
        $configuredDevices = $this->allocationService->getBootOrder($server);
        $unconfiguredDevices = [];

        foreach ($availableDevices as $device) {
            if ($configuredDevices->where('interface', '=', $device->interface)->first() === null) {
                array_push($unconfiguredDevices, $device);
            }
        }

        return fractal()->item([
            'unused_devices' => DiskData::collection($unconfiguredDevices),
            'boot_order' => $configuredDevices,
        ], new ServerBootOrderTransformer)->respond();
    }

    public function updateBootOrder(UpdateBootOrderRequest $request, Server $server)
    {
        $this->allocationService->setBootOrder($server, $request->order);

        return $this->returnNoContent();
    }

    public function getMedia(Request $request, Server $server)
    {
        $disks = $this->allocationService->getDisks($server);
        if ($request->user()->root_admin) {
            $media = $server->node->isos()->where('is_successful', '=', true)->get()->toArray();
        } else {
            $media = $server->node->isos()->where([['hidden', '=', false], ['is_successful', '=', true]])->get()->toArray();
        }

        $media = array_map(function ($iso) use ($disks) {
            if ($disks->where('media_name', '=', $iso['name'])->first()) {
                return [
                    'mounted' => true,
                    ...$iso,
                ];
            } else {
                return [
                    'mounted' => false,
                    ...$iso,
                ];
            }
        }, $media);

        return fractal($media, new MediaTransformer())->respond();
    }

    public function mountMedia(MountMediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->mountIso($server, $iso);

        return $this->returnNoContent();
    }

    public function unmountMedia(Server $server, ISO $iso)
    {
        $this->allocationService->unmountIso($server, $iso);

        return $this->returnNoContent();
    }

    public function getNetwork(Server $server)
    {
        return fractal()->item([
            'nameservers' => $this->cloudinitService->getNameservers($server),
        ], new ServerNetworkTransformer())->respond();
    }

    public function updateNetwork(UpdateNetworkRequest $request, Server $server)
    {
        $this->cloudinitService->updateNameservers($server, $request->nameservers);

        return fractal()->item([
            'nameservers' => $this->cloudinitService->getNameservers($server),
        ], new ServerNetworkTransformer())->respond();
    }

    public function getSecurity(Server $server)
    {
        return fractal()->item([
            'ssh_keys' => $this->authService->getSSHKeys($server),
        ], new ServerSecurityTransformer)->respond();
    }

    public function updateSecurity(UpdateSecurityRequest $request, Server $server)
    {
        if (AuthenticationType::from($request->type) === AuthenticationType::KEY) {
            $this->authService->updateSSHKeys($server, $request->ssh_keys);
        } else {
            $this->authService->updatePassword($server, $request->password);
        }

        return $this->returnNoContent();
    }
}
