<?php

namespace App\Services\Servers;

use App\Actions\Server\BuildServerAction;
use App\Data\Cluster\ServerResourceData;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\ServerStatus;
use App\Exceptions\Repository\Proxmox\NextVMIDRetrievalException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Exceptions\Service\Server\Allocation\NoUniqueUuidComboException;
use App\Exceptions\Service\Server\Allocation\NoUniqueVmidException;
use App\Models\Address;
use App\Models\Node;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Cluster\ProxmoxResourceRepository;
use App\Repositories\Proxmox\Node\ProxmoxAllocationRepository;
use App\Services\Addresses\AddressAllocationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

use function collect;
use function now;
use function substr;

/**
 * Class ServerCreationService
 */
class ServerCreationService
{
    public function __construct(
        private ServerNetworkService $networkService,
        private BuildServerAction $buildServerAction,
        private ProxmoxAllocationRepository $allocationRepository,
        private ProxmoxResourceRepository $resourceRepository,
        private AddressAllocationService $addressAllocationService,
    ) {}

    /**
     * @throws NoUniqueVmidException
     * @throws NextVMIDRetrievalException
     * @throws NoUniqueUuidComboException
     * @throws InsufficientAddressesException
     * @throws RequestException
     * @throws ConnectionException
     * @throws Throwable
     */
    public function handle(array $data): Server
    {
        return DB::transaction(function () use ($data) {
            $uuid = $this->generateUniqueUuidCombo();
            $node = Node::find($data['node_id']);

            $addresses = collect();
            if (Arr::has($data, 'limits.addresses') && ! empty(Arr::get($data, 'limits.addresses'))) {
                $addresses = Address::findMany(Arr::get($data, 'limits.addresses'))->load('addressBlock');
            } else {
                $ipv4Count = (int) Arr::get($data, 'limits.addresses_ipv4_count', 0);
                $ipv6Count = (int) Arr::get($data, 'limits.addresses_ipv6_count', 0);
                if ($ipv4Count > 0 || $ipv6Count > 0) {
                    $addresses = $this->addressAllocationService->handle($data['limits']['network_interface_id'], $ipv4Count, $ipv6Count);
                }
            }

            // forceCreate (not create): uuid / uuid_short are $guarded, so plain
            // mass-assignment silently drops them and the NOT NULL uuid column
            // blows up. forceCreate assigns them while unguarded; save-time
            // validation is unchanged.
            $server = Server::forceCreate([
                'uuid' => $uuid,
                'uuid_short' => substr($uuid, 0, 8),
                'user_id' => $data['user_id'],
                'node_id' => $node->id,
                'network_interface_id' => Arr::get($data, 'limits.network_interface_id'),
                'storage_id' => $data['storage_id'],
                'vmid' => $data['vmid'] ?? $this->generateUniqueVmId($node),
                'hostname' => $data['hostname'],
                'name' => $data['name'],
                'description' => Arr::get($data, 'description'),
                'status' => $data['deferred_os_selection'] ? ServerStatus::DEFERRED_OS_SELECTION : ServerStatus::INSTALLING,
                'cpu' => $data['limits']['cpu'],
                'memory' => $data['limits']['memory'],
                'disk' => $data['limits']['disk'],
                'primary_ipv4_address_id' => $addresses->firstWhere('version', 'IPv4')?->id,
                'primary_ipv6_address_id' => $addresses->firstWhere('version', 'IPv6')?->id,
                'backup_count_limit' => Arr::get($data, 'limits.backups.count'),
                'backup_size_limit' => Arr::get($data, 'limits.backups.size'),
                'bandwidth_limit' => Arr::get($data, 'limits.bandwidth'),
                'speed_limit' => Arr::get($data, 'limits.speed_limit'),
                // Anchor the monthly quota reset to today's day-of-month; a seam
                // Paymenter can later point at the real renewal date (see the plan §6).
                'bandwidth_reset_day' => now()->day,
                'vlan_tag' => Arr::get($data, 'limits.vlan_tag'),
            ]);

            // Mirror the primary disk into server_disks. Expand-first: the
            // servers.(storage_id, disk) columns remain authoritative for the
            // clone; this row is what the disk-oriented usage aggregation and
            // (later) secondary disks build on.
            $server->disks()->create([
                'storage_id' => $server->storage_id,
                'size' => $data['limits']['disk'],
                'interface' => null,
                'is_primary' => true,
                'disk_index' => 0,
            ]);

            // Secondary/data disks, each on its own storage. interface is null
            // until the build allocates them (AllocationService::syncDisks).
            foreach (array_values(Arr::get($data, 'limits.disks', [])) as $index => $disk) {
                $server->disks()->create([
                    'storage_id' => $disk['storage_id'],
                    'size' => $disk['size'],
                    'interface' => null,
                    'is_primary' => false,
                    'disk_index' => $index + 1,
                ]);
            }

            if ($addresses->isNotEmpty()) {
                $this->networkService->syncAddresses($server, $addresses->pluck('id')->all());
            }

            if (! $data['deferred_os_selection']) {
                $templateUuid = Arr::get($data, 'template_uuid');

                $deployment = $server->deployments()->create([
                    'template_id' => filled($templateUuid) ? Template::where('uuid', $templateUuid)->value('id') : null,
                    'type' => $data['should_create_vm'] ? DeploymentType::INSTALL : DeploymentType::IMPORT,
                    'status' => DeploymentStatus::PENDING,
                    'start_on_completion' => $data['start_on_completion'],
                    'requested_at' => now(),
                ]);

                $this->buildServerAction->execute($deployment, Arr::get($data, 'account_password'));
            }

            return $server;
        });
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isTemplateAvailable(Node $node, string $templateUuid): bool
    {
        return filled($this->getTemplate($node, $templateUuid));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getTemplate(Node $node, string $templateUuid): ?ServerResourceData
    {
        return $this->resourceRepository->setNode($node)->getResources()
            ->where('vmid', Template::where('uuid', $templateUuid)->value('vmid'))
            ->where('isTemplate', true)
            ->first();
    }

    /**
     * @throws NoUniqueVmidException
     * @throws NextVMIDRetrievalException
     */
    public function generateUniqueVmId(Node $node): int
    {
        $vmid = $this->allocationRepository->setNode($node)->getNextVMID();
        $attempts = 0;

        while (true) {
            // Check uniqueness in our database
            if (Server::isUniqueVmId($node, $vmid)) {
                // Check uniqueness in Proxmox
                if ($this->allocationRepository->isVMIDAvailable($vmid)) {
                    break;
                }

                $vmid++;
            } else {
                $vmid++;
            }

            if ($attempts++ > 10) {
                throw new NoUniqueVmidException;
            }
        }

        return $vmid;
    }

    /**
     * @throws NoUniqueUuidComboException
     */
    public function generateUniqueUuidCombo(): string
    {
        $uuid = Str::uuid()->toString();
        $short = substr($uuid, 0, 8);
        $attempts = 0;

        while (! Server::isUniqueUuidCombo($uuid, $short)) {
            $uuid = Str::uuid()->toString();
            $short = substr($uuid, 0, 8);

            if ($attempts++ > 10) {
                throw new NoUniqueUuidComboException;
            }
        }

        return $uuid;
    }
}
