<?php

namespace App\Http\Controllers\Client\Servers;

use App\Actions\Server\RebuildServerAction;
use App\Data\Server\Proxmox\Config\DiskData;
use App\Data\Server\RenamedServerData;
use App\Data\Server\ServerNetworkSettingsData;
use App\Data\Server\ServerSecuritySettingsData;
use App\Data\Server\ServerStorageData;
use App\Data\Server\StorageDeviceData;
use App\Data\Template\TemplateGroupData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\AuthenticationType;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Facades\Audit;
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

        Audit::record(
            AuditEvent::SERVER_RENAMED,
            subject: $server,
            properties: ['hostname' => $request->hostname],
        );

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

            // Inside the transaction: a reinstall that rolls back must not leave a record
            // claiming it happened. The account password is deliberately not recorded.
            Audit::record(
                AuditEvent::SERVER_REINSTALLED,
                subject: $server,
                properties: [
                    'template' => $template->name,
                    'template_uuid' => $template->uuid,
                    'start_on_completion' => $request->boolean('start_on_completion'),
                ],
            );
        });

        return response()->noContent();
    }

    public function getStorage(Server $server)
    {
        // Both halves come out of one config read. Asking the service for disks
        // and boot order separately would issue the same PVE request twice.
        $config = $this->allocationService->getConfig($server);

        return new ServerStorageData(
            devices: $config->disks
                ->values()
                ->map(fn (DiskData $disk) => StorageDeviceData::fromDisk($disk)),
            bootOrder: $config->bootOrder
                ->map(fn (DiskData $disk) => $disk->interface->value)
                ->values()
                ->all(),
        );
    }

    public function updateBootOrder(UpdateBootOrderRequest $request, Server $server)
    {
        $this->allocationService->setBootOrder($server, $request->order);

        Audit::record(
            AuditEvent::SERVER_BOOT_ORDER_UPDATED,
            subject: $server,
            properties: ['order' => $request->order],
        );

        return response()->noContent();
    }

    public function getSerialConsole(Server $server)
    {
        return $this->serialConsoleService->status($server);
    }

    public function enableSerialConsole(Server $server)
    {
        $result = $this->serialConsoleService->enable($server);

        Audit::record(AuditEvent::SERVER_CONSOLE_SERIAL_ENABLED, subject: $server);

        return $result;
    }

    public function getDisplayConsole(Server $server)
    {
        return $this->displayConsoleService->status($server);
    }

    public function enableDisplayConsole(Server $server)
    {
        $result = $this->displayConsoleService->enable($server);

        Audit::record(AuditEvent::SERVER_CONSOLE_DISPLAY_ENABLED, subject: $server);

        return $result;
    }

    public function getMedia(Request $request, Server $server)
    {
        $disks = $this->allocationService->getDisks($server);

        $query = $server->node->isos()->with('storage')->where('is_successful', '=', true);

        if (! $request->user()->root_admin) {
            $query->where('hidden', '=', false);
        }

        return $query->get()->map(fn (ISO $iso) => [
            'uuid' => $iso->uuid,
            'name' => $iso->name,
            'size' => $iso->size,
            'hidden' => $iso->hidden,
            // Matched on the backing volume, the same way mount and unmount
            // locate it. The previous check compared a `media_name` property
            // DiskData has never had, so every ISO reported itself unmounted.
            'mounted' => $this->allocationService->findMountedIsoDisk($disks, $iso) !== null,
        ])->all();
    }

    public function mountMedia(MediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->mountIso($server, $iso);

        Audit::record(
            AuditEvent::SERVER_MEDIA_MOUNTED,
            subject: $server,
            properties: ['iso' => $iso->name, 'iso_uuid' => $iso->uuid],
        );

        return response()->noContent();
    }

    public function unmountMedia(MediaRequest $request, Server $server, ISO $iso)
    {
        $this->allocationService->unmountIso($server, $iso);

        Audit::record(
            AuditEvent::SERVER_MEDIA_UNMOUNTED,
            subject: $server,
            properties: ['iso' => $iso->name, 'iso_uuid' => $iso->uuid],
        );

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

        Audit::record(
            AuditEvent::SERVER_NETWORK_SETTINGS_UPDATED,
            subject: $server,
            properties: ['nameservers' => $request->nameservers],
        );

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
        $type = AuthenticationType::from($request->type);

        if ($type === AuthenticationType::KEY) {
            $this->authService->setSSHKeys($server, $request->ssh_keys);
        } else {
            $this->authService->setPassword($server, $request->password);
        }

        // The type and, for keys, how many were set — never the password or the key material.
        Audit::record(
            AuditEvent::SERVER_AUTH_SETTINGS_UPDATED,
            subject: $server,
            properties: array_filter([
                'type' => $type->value,
                'ssh_key_count' => $type === AuthenticationType::KEY
                    ? count($request->ssh_keys ?? [])
                    : null,
            ], fn ($value) => $value !== null),
        );

        return response()->noContent();
    }
}
