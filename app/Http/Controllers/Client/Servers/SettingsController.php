<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\Deployments\ServerDeploymentData;
use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\AuthenticationType;
use App\Enums\Server\ServerStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\DeploymentStatus;
use App\Http\Requests\Client\Servers\Settings\MountMediaRequest;
use App\Http\Requests\Client\Servers\Settings\ReinstallServerRequest;
use App\Http\Requests\Client\Servers\Settings\RenameServerRequest;
use App\Http\Requests\Client\Servers\Settings\UpdateAuthSettingsRequest;
use App\Http\Requests\Client\Servers\Settings\UpdateBootOrderRequest;
use App\Http\Requests\Client\Servers\Settings\UpdateNetworkRequest;
use App\Models\ISO;
use App\Models\Server;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Services\Servers\AllocationService;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\ServerAuthService;
use App\Actions\Server\RebuildServerAction;
use App\Transformers\Client\MediaTransformer;
use App\Transformers\Client\RenamedServerTransformer;
use App\Transformers\Client\ServerBootOrderTransformer;
use App\Transformers\Client\ServerNetworkTransformer;
use App\Transformers\Client\ServerSecurityTransformer;
use App\Transformers\Client\TemplateGroupTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SettingsController
{
    public function __construct(
        private ServerAuthService $authService,
        private ConnectionInterface $connection,
        private CloudinitService $cloudinitService,
        private RebuildServerAction $rebuildServerAction,
        private AllocationService $allocationService,
    ) {}

    public function rename(RenameServerRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $this->cloudinitService->setHostname($server, $request->hostname);

            $server->update($request->validated());
        });

        return fractal($server, new RenamedServerTransformer)->respond();
    }

    public function getTemplateGroups(Request $request, Server $server)
    {
        $isAdmin = $request->user()->root_admin;

        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->allowedFilters(['name']);

        if (! $isAdmin) {
            $templateGroups->where('is_admin_only', false);
        }

        $templateGroups = $templateGroups->with(['templates' => function ($query) use ($isAdmin) {
            if (! $isAdmin) {
                $query->where('is_admin_only', false);
            }
        }])->get();

        return fractal($templateGroups, new TemplateGroupTransformer)->respond();
    }

    public function reinstall(ReinstallServerRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $template = Template::where('uuid', '=', $request->template_uuid)->firstOrFail();

            $deployment = $server->deployments()->create([
                'template_id' => $template->id,
                'type' => DeploymentType::REINSTALL,
                'status' => DeploymentStatus::PENDING,
                'start_on_completion' => $request->boolean('start_on_completion'),
                'requested_at' => now(),
            ]);

            $this->rebuildServerAction->execute($deployment, $request->account_password);
        });

        return response()->noContent();
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
            'unused_devices' => DiskData::collect($unconfiguredDevices),
            'boot_order' => $configuredDevices,
        ], new ServerBootOrderTransformer)->respond();
    }

    public function updateBootOrder(UpdateBootOrderRequest $request, Server $server)
    {
        $this->allocationService->setBootOrder($server, $request->order);

        return response()->noContent();
    }

    public function getMedia(Request $request, Server $server)
    {
        $disks = $this->allocationService->getDisks($server);
        if ($request->user()->root_admin) {
            $media = $server->node->isos()->where('is_successful', '=', true)->get()->toArray();
        } else {
            $media = $server->node->isos()->where(
                [['hidden', '=', false], ['is_successful', '=', true]],
            )->get()->toArray();
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

        return fractal($media, new MediaTransformer)->respond();
    }

    public function mountMedia(MountMediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->mountIso($server, $iso);

        return response()->noContent();
    }

    public function unmountMedia(Server $server, ISO $iso)
    {
        $this->allocationService->unmountIso($server, $iso);

        return response()->noContent();
    }

    public function getNetworkSettings(Server $server)
    {
        return fractal()->item([
            'nameservers' => $this->cloudinitService->getNameservers($server),
        ], new ServerNetworkTransformer)->respond();
    }

    public function updateNetworkSettings(UpdateNetworkRequest $request, Server $server)
    {
        $this->cloudinitService->setNameservers($server, $request->nameservers);

        return fractal()->item([
            'nameservers' => $this->cloudinitService->getNameservers($server),
        ], new ServerNetworkTransformer)->respond();
    }

    public function getAuthSettings(Server $server)
    {
        return fractal()->item([
            'ssh_keys' => $this->authService->getSSHKeys($server),
        ], new ServerSecurityTransformer)->respond();
    }

    public function updateAuthSettings(UpdateAuthSettingsRequest $request, Server $server)
    {
        if (AuthenticationType::from($request->type) === AuthenticationType::KEY) {
            $this->authService->setSSHKeys($server, $request->ssh_keys);
        } else {
            $this->authService->setPassword($server, $request->password);
        }

        return response()->noContent();
    }
}
