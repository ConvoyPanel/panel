<?php

namespace App\Http\Controllers\Client\Servers;

use App\Actions\Server\RebuildServerAction;
use App\Data\Server\BootOrderData;
use App\Data\Server\RenamedServerData;
use App\Data\Server\ServerNetworkSettingsData;
use App\Data\Server\ServerSecuritySettingsData;
use App\Data\Template\TemplateGroupData;
use App\Enums\Server\AuthenticationType;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Http\Requests\Client\Servers\Settings\MediaRequest;
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
use App\Services\Servers\DisplayConsoleService;
use App\Services\Servers\SerialConsoleService;
use App\Services\Servers\ServerAuthService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

class SettingsController
{
    public function __construct(
        private ServerAuthService $authService,
        private ConnectionInterface $connection,
        private CloudinitService $cloudinitService,
        private RebuildServerAction $rebuildServerAction,
        private AllocationService $allocationService,
        private SerialConsoleService $serialConsoleService,
        private DisplayConsoleService $displayConsoleService,
    ) {}

    public function rename(RenameServerRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $this->cloudinitService->setHostname($server, $request->hostname);

            $server->update($request->validated());
        });

        return RenamedServerData::from($server);
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

        return TemplateGroupData::collect($templateGroups, DataCollection::class)
            ->include('templates');
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

        $unconfiguredDevices = $availableDevices
            ->filter(fn ($device) => $configuredDevices
                ->where('interface', '=', $device->interface)
                ->first() === null)
            ->values();

        return new BootOrderData(
            unusedDevices: $unconfiguredDevices,
            bootOrder: $configuredDevices->values(),
        );
    }

    public function updateBootOrder(UpdateBootOrderRequest $request, Server $server)
    {
        $this->allocationService->setBootOrder($server, $request->order);

        return response()->noContent();
    }

    public function getSerialConsole(Server $server)
    {
        return $this->serialConsoleService->status($server);
    }

    public function enableSerialConsole(Server $server)
    {
        return $this->serialConsoleService->enable($server);
    }

    public function getDisplayConsole(Server $server)
    {
        return $this->displayConsoleService->status($server);
    }

    public function enableDisplayConsole(Server $server)
    {
        return $this->displayConsoleService->enable($server);
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

        return array_map(function ($iso) use ($disks) {
            $isMounted = (bool) $disks->where('media_name', '=', $iso['name'])->first();

            return [
                'uuid' => $iso['uuid'],
                'name' => $iso['name'],
                'size' => $iso['size'],
                'hidden' => $iso['hidden'],
                'mounted' => $isMounted,
            ];
        }, $media);
    }

    public function mountMedia(MediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->mountIso($server, $iso);

        return response()->noContent();
    }

    public function unmountMedia(MediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->unmountIso($server, $iso);

        return response()->noContent();
    }

    public function getNetworkSettings(Server $server)
    {
        return new ServerNetworkSettingsData(
            nameservers: $this->cloudinitService->getNameservers($server),
        );
    }

    public function updateNetworkSettings(UpdateNetworkRequest $request, Server $server)
    {
        $this->cloudinitService->setNameservers($server, $request->nameservers);

        return new ServerNetworkSettingsData(
            nameservers: $this->cloudinitService->getNameservers($server),
        );
    }

    public function getAuthSettings(Server $server)
    {
        return new ServerSecuritySettingsData(
            sshKeys: $this->authService->getSSHKeys($server),
        );
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
