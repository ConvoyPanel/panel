<?php

namespace App\Services\Servers;

use App\Models\Node;
use App\Models\Deployment;
use Random\RandomException;
use App\Data\Server\Deployments\ServerDeploymentData;
use App\Enums\Server\ServerStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\Node\ProxmoxAllocationRepository;
use App\Exceptions\Service\Deployment\InvalidTemplateException;
use App\Repositories\Proxmox\Cluster\ProxmoxResourceRepository;
use App\Exceptions\Repository\Proxmox\NextVMIDRetrievalException;
use App\Exceptions\Service\Server\Allocation\NoUniqueUuidComboException;
use App\Exceptions\Service\Server\Allocation\NoUniqueVmidException;
use App\Models\Address;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Eloquent\ServerRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use function collect;
use function array_get;

/**
 * Class ServerCreationService
 */
class ServerCreationService
{
    public function __construct(
        private ServerNetworkService       $networkService,
        private ServerRepository           $repository,
        private ServerBuildDispatchService $buildDispatchService,
        private ProxmoxAllocationRepository $allocationRepository,
        private ProxmoxResourceRepository $resourceRepository,
    ) {
    }

    /**
     * @throws NoUniqueVmidException
     * @throws NextVMIDRetrievalException
     * @throws NoUniqueUuidComboException
     * @throws RequestException
     * @throws InvalidTemplateException
     */
    public function handle(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();
        $nodeId = $data['node_id'];
        $addresses = Address::findMany(Arr::get($data, 'limits.address_ids', []))->load('addressBlock');

        $server = Server::create([
            'uuid' => $uuid,
            'uuid_short' => substr($uuid, 0, 8),
            'user_id' => $data['user_id'],
            'node_id' => $nodeId,
            'vmid' => $data['vmid'] ?? $this->generateUniqueVmId($nodeId),
            'hostname' => $data['hostname'],
            'name' => $data['name'],
            'description' => array_get($data, 'description'),
            'status' => $data['deferred_os_selection'] ? ServerStatus::READY : ($data['should_create_vm'] ? ServerStatus::INSTALLING : null),
            'cpu' => $data['limits']['cpu'],
            'memory' => $data['limits']['memory'],
            'disk' => $data['limits']['disk'],
            'primary_ipv4_address_id' => $addresses->firstWhere('version', 'IPv4')?->id,
            'primary_ipv6_address_id' => $addresses->firstWhere('version', 'IPv6')?->id,
            'snapshot_limit' => Arr::get($data, 'limits.snapshots'),
            'backup_limit' => Arr::get($data, 'limits.backups'),
            'bandwidth_limit' => Arr::get($data, 'limits.bandwidth'),
        ]);


        $deployment = ServerDeploymentData::from([
            'server' => $server,
            'account_password' => Arr::get($data, 'account_password'),
        ]);

        if ($addresses->isNotEmpty()) {
            $this->networkService->syncAddresses($server, $addresses->pluck('id')->all());
        }

        $this->buildDispatchService->build($deployment);

        return $server;
    }

    public function isTemplateAvailable(Node $node, int $vmid): bool
    {
        $this->resourceRepository->setNode($node);
        $template = collect($this->resourceRepository->getResources())
            ->where('vmid', $vmid)
            ->where('template', true)
            ->first();

        return filled($template);
    }

    /**
     * @throws NoUniqueVmidException
     * @throws NextVMIDRetrievalException
     * @throws RequestException
     */
    public function generateUniqueVmId(int $nodeId): int
    {
        $vmid = $this->allocationRepository->getNextVMID();
        $attempts = 0;

        while (true) {
            // Check uniqueness in our database
            if ($this->repository->isUniqueVmId($nodeId, $vmid)) {
                // Check uniqueness in Proxmox
                $nextVmid = $this->allocationRepository->isVMIDAvailable($vmid);
                if ($nextVmid === $vmid) {
                    break;
                }
                $vmid = $nextVmid;
            } else {
                $vmid++;
            }

            if ($attempts++ > 10) {
                throw new NoUniqueVmidException();
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

        while (! $this->repository->isUniqueUuidCombo($uuid, $short)) {
            $uuid = Str::uuid()->toString();
            $short = substr($uuid, 0, 8);

            if ($attempts++ > 10) {
                throw new NoUniqueUuidComboException();
            }
        }

        return $uuid;
    }
}
