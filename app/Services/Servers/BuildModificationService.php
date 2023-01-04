<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\Power;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Support\Arr;

class BuildModificationService
{
    public function __construct(
        private AllocationService $allocationService,
        private CloudinitService $cloudinitService,
        private NetworkService $networkService,
        private ServerDetailService $detailService,
        private ProxmoxPowerRepository $powerRepository,
        private ProxmoxAllocationRepository $allocationRepository,
    ) {
    }

    public function handle(Server $server, ?bool $shouldUpdateState = true)
    {
        $this->networkService->setServer($server);
        $this->allocationService->setServer($server);
        $this->allocationRepository->setServer($server);

        $eloquentDetails = $this->detailService->getByEloquent($server);
        $proxmoxDetails = $this->detailService->getByProxmox($server);

        $this->allocationService->updateHardware($server, $eloquentDetails->limits->cpu, $eloquentDetails->limits->memory);

        /* Sync metadata */
        $this->cloudinitService->updateHostname($server, $eloquentDetails->hostname);

        /* Sync network configuration */
        $this->networkService->syncSettings($server);

        // find a disk that has a corresponding disk in the deployment
        $disks = collect($proxmoxDetails->config->disks->toArray())->pluck('name')->all();
        $bootOrder = array_filter(collect($proxmoxDetails->config->boot_order->filter(fn ($disk) => $disk->type !== 'media')->toArray())->pluck('name')->toArray(), fn ($disk) => in_array($disk, $disks));

        if (count($bootOrder) > 0) {
            $disk = $proxmoxDetails->config->disks->where('name', '=', Arr::first($bootOrder))->first();

            $diff = $eloquentDetails->limits->disk - $disk->size;

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
