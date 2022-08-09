<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class InstallService extends ProxmoxService
{
    private NetworkService $networkService;
    private CloudinitService $cloudinitService;
    private ServerDetailService $detailService;
    private ProxmoxServerRepository $serverRepository;
    private ProxmoxAllocationRepository $allocationRepository;
    private AllocationService $allocationService;
    private ProxmoxPowerRepository $powerRepository;

    public function __construct()
    {
        $this->networkService = new NetworkService;
        $this->cloudinitService = new CloudinitService;
        $this->detailService = new ServerDetailService;
        $this->serverRepository = new ProxmoxServerRepository;
        $this->allocationRepository = new ProxmoxAllocationRepository;
        $this->allocationService = new AllocationService;
        $this->powerRepository = new ProxmoxPowerRepository;
    }

    public function delete()
    {
        /*
         * Procedure
         *
         * 1. Get the server details
         * 2. Power off the server
         * 3. Delete the server
         * 4. Return the server details
         */

        Assert::isInstanceOf($this->server, Server::class);
        $this->detailService->setServer($this->server);
        $this->serverRepository->setServer($this->server);
        $this->powerRepository->setServer($this->server);

        /* 1. Get the server details */
        $details = $this->detailService->getDetails();

        /* 2. Power off the server */
        $this->powerRepository->send('stop');

        // Wait for server to turn off
        $intermissionStatus = $this->serverRepository->getStatus();

        if (Arr::get($intermissionStatus, 'status') !== 'stopped') {
            do {
                $intermissionStatus = $this->serverRepository->getStatus();
            } while (Arr::get($intermissionStatus, 'status') !== 'stopped');
        }

        /* 3. Delete the server */
        $this->serverRepository->delete();

        /* 4. Return the server details */
        return $details;
    }

    public function install(Template $template, array $details)
    {
        /*
         * Procedure
         *
         * 1. Clone the template
         * 2. Configure the specifications
         * 3. Configure the IPs
         * 4. Configure the disks
         * 5. Kill the server to guarantee configurations are active
         */

        Assert::isInstanceOf($this->server, Server::class);
        $this->allocationRepository->setServer($this->server);
        $this->allocationService->setServer($this->server);
        $this->detailService->setServer($this->server);
        $this->cloudinitService->setServer($this->server);
        $this->networkService->setServer($this->server);
        $this->powerRepository->setServer($this->server);
        $this->serverRepository->setServer($this->server);

        /* 1. Clone the template */
        $this->serverRepository->create($template->server->vmid);

        // Wait until cloning is complete
        $intermissionDetails = $this->detailService->getDetails();

        if (Arr::get($intermissionDetails, 'locked')) {
            do {
                $intermissionDetails = $this->detailService->getDetails();
            } while (Arr::get($intermissionDetails, 'locked'));
        }

        /* 2. Configure the specifications */
        $this->allocationService->updateSpecifications([
            'cpu' => Arr::get($details, 'limits.cpu'),
            'memory' => Arr::get($details, 'limits.memory'),
        ]);

        /* 3. Configure the IPs */
        $this->cloudinitService->updateIpConfig(Arr::get($details, 'limits.addresses'));
        $this->networkService->lockIps(Arr::flatten($this->server->addresses()->get(['address'])->toArray()));

        /* 4. Configure the disks */
        $templateDetails = $this->detailService->getDetails();

        // Assume the first entry in the boot disks will be the one to resize. All other disks will be dynamically resized/recreated, but this behavior guarantees that a hosting provider can set the disk size no matter the disk type
        $primaryDisk = collect(Arr::get($details, 'configuration.disks'))->where('disk', Arr::first(Arr::get($details, 'configuration.boot_order')))->first();
        $templatePrimaryDisk = collect(Arr::get($templateDetails, 'configuration.disks'))->where('disk', Arr::first(Arr::get($templateDetails, 'configuration.boot_order')))->first();

        if ($primaryDisk !== null && $templatePrimaryDisk !== null)
        {
            // If there's no primary disk, then we don't have to do any resizing. Easy!
            $diff = $this->allocationService->convertToBytes($primaryDisk['size']) - $this->allocationService->convertToBytes($templatePrimaryDisk['size']);
            $this->allocationRepository->resizeDisk($diff, $templatePrimaryDisk['disk']);
        }

        /* 5. Kill the server to guarantee configurations are active */
        $this->powerRepository->send('stop');

        return $this->detailService->getDetails();
    }

    public function reinstall(Template $template)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $details = $this->delete();

        return $this->install($template, $details);
    }

    public function convertToBytes(string $from): ?int
    {
        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $number = substr($from, 0, -1);
        $suffix = strtoupper(substr($from, -1));

        //B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        if ($exponent === null) {
            return null;
        }

        return $number * (1024 ** $exponent);
    }
}
