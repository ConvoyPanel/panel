<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Proxmox\Config\DiskData;
use Convoy\Enums\Server\PowerAction;
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

        $this->allocationService->syncSettings($server);

        /* Sync metadata */
        $this->cloudinitService->updateHostname($server, $eloquentDetails->hostname);

        /* Sync network configuration */
        $this->networkService->syncSettings($server);

        // find a disk that has a corresponding disk in the deployment
        $disks = collect($proxmoxDetails->config->disks->toArray())->pluck('interface')->all();
        $bootOrder = array_filter(collect($proxmoxDetails->config->boot_order->filter(fn (DiskData $disk) => !$disk->is_media)->toArray())->pluck('interface')->toArray(), fn ($disk) => in_array($disk, $disks));

        if (count($bootOrder) > 0) {
            /** @var DiskData */
            $disk = $proxmoxDetails->config->disks->where('interface', '=', Arr::first($bootOrder))->first();

            $diff = $eloquentDetails->limits->disk - $disk->size;

            if ($diff > 0) {
                $this->allocationRepository->resizeDisk($diff, $disk->interface);
            }
        }

        /* Persist configuration immediately */

        if ($shouldUpdateState) {
            $this->powerRepository->setServer($server)->send(PowerAction::KILL);
        }
    }
}
