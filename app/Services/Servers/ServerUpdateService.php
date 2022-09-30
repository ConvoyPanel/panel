<?php

namespace Convoy\Services\Servers;

use Convoy\Models\Objects\Server\ServerSpecificationsObject;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ServerUpdateService extends ProxmoxService
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


    public function handle(ServerSpecificationsObject $deployment)
    {
        Assert::isInstanceOf($this->server, Server::class);
        $this->allocationService->setServer($this->server);
        $this->networkService->setServer($this->server);
        $this->cloudinitService->setServer($this->server);
        $this->detailService->setServer($this->server);
        $this->allocationRepository->setServer($this->server);
        $this->powerRepository->setServer($this->server);

        /* 2. Configure the specifications */
        if ($deployment->limits?->cpu || $deployment->limits?->memory)
            $this->allocationService->updateSpecifications([
                'cpu' => $deployment->limits?->cpu,
                'memory' => $deployment->limits?->memory,
            ]);

        /* 3. Configure the IPs */
        if ((int) $deployment->limits?->addresses?->ipv4->count() !== 0  || (int) $deployment->limits?->addresses?->ipv6->count() !== 0) {
            $this->networkService->clearIpsets();

            $this->cloudinitService->updateIpConfig([
                'ipv4' => $deployment->limits->addresses?->ipv4->first()?->toArray() ?? [],
                'ipv6' => $deployment->limits->addresses?->ipv6->first()?->toArray() ?? [],
            ]);

            $this->networkService->lockIps(Arr::flatten($this->server->addresses()->get(['address'])->toArray()));
        }

        if (isset($deployment->limits?->addresses?->ipv4?->first()->mac_address)) {
            $this->networkService->updateMacAddress($deployment->limits?->addresses?->ipv4?->first()->mac_address);
        } elseif (isset($deployment->limits?->addresses?->ipv6?->first()->mac_address)) {
            $this->networkService->updateMacAddress($deployment->limits?->addresses?->ipv6?->first()->mac_address);
        }

        if ($deployment->limits?->disk) {
            /* 4. Configure the disks */
            $templateDetails = $this->detailService->getDetails();

            // Assume the first entry in the boot disks will be the one to resize. All other disks will be dynamically resized/recreated, but this behavior guarantees that a hosting provider can set the disk size no matter the disk type
            $templatePrimaryDisk = $templateDetails->config?->disks->where(key: 'disk', value: Arr::first($templateDetails->config?->boot_order))->first();

            if ($templatePrimaryDisk) {
                // If there's no primary disk, then we don't have to do any resizing. Easy!
                $diff = $deployment->limits->disk - $templatePrimaryDisk->size;

                if ($diff > 0)
                    $this->allocationRepository->resizeDisk($diff, $templatePrimaryDisk->disk);
            }
        }

        /* 5. Kill the server to guarantee configurations are active */
        $this->powerRepository->send('stop');

        return $this->detailService->getDetails();
    }
}
