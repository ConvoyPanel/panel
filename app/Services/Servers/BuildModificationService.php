<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Enums\Server\Power;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Support\Arr;

class BuildModificationService
{
    public function __construct(
        protected AllocationService $allocationService,
        protected CloudinitService $cloudinitService,
        protected NetworkService $networkService,
        protected ServerDetailService $detailService,
        protected ProxmoxPowerRepository $powerRepository,
        protected ProxmoxAllocationRepository $allocationRepository,
    ) {
    }

    public function handle(Server $server, ?bool $shouldUpdateState = true)
    {
        $this->networkService->setServer($server);
        $this->allocationService->setServer($server);

        $deployment = $this->detailService->getByProxmox($server);

        $this->allocationService->updateHardware($server, $deployment->limits->cpu, $deployment->limits->memory);

        /* Sync metadata */
        $this->cloudinitService->updateHostname($server, $deployment->hostname);

        /* Sync network configuration */
        $this->networkService->syncSettings($server, $deployment);

        /* Sync disk configuration */

        // find a disk that has a corresponding disk in the deployment
        $disks = collect($deployment->config->disks->toArray())->pluck('name')->all();
        $bootOrder = array_filter($deployment->config->boot_order, fn ($disk) => in_array($disk, $disks));

        if (count($bootOrder) > 0) {
            $disk = $deployment->config->disks->where('name', '=', Arr::first($bootOrder))->first();

            $diff = $deployment->limits->disk - $disk->size;

            if ($diff > 0) {
                $this->allocationRepository->resizeDisk($diff, $disk->name);
            }
        }

        /* Persist configuration immediately */

        if ($shouldUpdateState) {
            $this->powerRepository->setServer($server)->send(Power::KILL);
        }
    }
}
