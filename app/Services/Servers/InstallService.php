<?php

namespace App\Services\Servers;

use App\Models\Server;
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
    private PowerService $powerService;
    private ResourceService $resourceService;
    private NetworkService $networkService;
    private CloudinitService $cloudinitService;
    private ServerDetailService $detailService;
    private ProxmoxServerRepository $serverRepository;
    private ProxmoxAllocationRepository $allocationRepository;
    private AllocationService $allocationService;
    private ProxmoxPowerRepository $powerRepository;

    public function __construct()
    {
        $this->powerService = new PowerService;
        $this->resourceService = new ResourceService;
        $this->networkService = new NetworkService;
        $this->cloudinitService = new CloudinitService;
        $this->detailService = new ServerDetailService;
        $this->serverRepository = new ProxmoxServerRepository;
        $this->allocationRepository = new ProxmoxAllocationRepository;
        $this->allocationService = new AllocationService;
        $this->powerRepository = new ProxmoxPowerRepository;
    }

    /*
    * @deprecated This function is redundant and is just a proxy to another function
    */
    public function install(int $newid, string $target)
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->instance()->clone()->post(['newid' => $newid, 'target' => $target, 'full' => true]);
    }

    /*
    * @deprecated This function is redundant and is just a proxy to another function
    */
    public function delete(bool $destroyUnreferencedDisks = true, bool $purgeJobConfigurations = true, bool $skiplock = true)
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->serverRepository->setServer($this->server)->delete($destroyUnreferencedDisks, $purgeJobConfigurations, $skiplock);
    }

    public function reinstall(Server $template)
    {
        /*
         * Procedure
         *
         * 1. Get the server details
         * 2. Delete the server
         * 3. Clone the template
         * 4. Configure the specifications
         * 5. Configure the disks
         * 6. Configure the IPs
         * 7. Kill the server to guarantee configurations are active
         */

        Assert::isInstanceOf($this->server, Server::class);
        $this->detailService->setServer($this->server);
        $this->serverRepository->setServer($this->server);
        $this->allocationService->setServer($this->server);
        $this->allocationRepository->setServer($this->server);
        $this->cloudinitService->setServer($this->server);
        $this->networkService->setServer($this->server);
        $this->powerRepository->setServer($this->server);

        /* 1. Get the server details */
        $details = $this->detailService->getDetails();

        /* 2. Delete the server */
        $this->serverRepository->delete();

        /* 3. Clone the template */
        $this->serverRepository->create($template->vmid);

        // Wait until cloning is complete
        $intermissionDetails = $this->detailService->getDetails();

        if (Arr::get($intermissionDetails, 'locked')) {
            do {
                $intermissionDetails = $this->detailService->getDetails();
                print 'waiting';
            } while (Arr::get($intermissionDetails, 'locked'));
        }

        /* 4. Configure the specifications */
        $this->allocationService->updateSpecifications([
            'cpu' => Arr::get($details, 'limits.cpu'),
            'memory' => Arr::get($details, 'limits.memory'),
        ]);
        print 'updated specs';

        /* 5. Configure the disks */
        $templateDetails = $this->detailService->getDetails();

        // Assume the first entry in the boot disks will be the one to resize. All other disks will be dynamically resized/recreated, but this behavior guarantees that a hosting provider can set the disk size no matter the disk type
        $primaryDisk = collect(Arr::get($details, 'configuration.disks'))->where('disk', Arr::first(Arr::get($details, 'configuration.boot_order')))->first();
        $templatePrimaryDisk = collect(Arr::get($templateDetails, 'configuration.disks'))->where('disk', Arr::first(Arr::get($templateDetails, 'configuration.boot_order')))->first();

        print 'get disks';
        if ($primaryDisk !== null && $templatePrimaryDisk !== null)
        {
            // If there's no primary disk, then we don't have to do any resizing. Easy!

            $diff = $this->allocationService->convertToBytes($primaryDisk['size']) - $this->allocationService->convertToBytes($templatePrimaryDisk['size']);
            $this->allocationRepository->resizeDisk($diff, $templatePrimaryDisk['disk']);

            echo 'updated disks';
        }

        /* 6. Configure the IPs */
        $ipconfig = [
            'ipv4' => $this->server->addresses()->where('type', 'ip')->first(['address' ,'cidr', 'gateway']),
            'ipv6' => $this->server->addresses()->where('type', 'ip6')->first(['address' ,'cidr', 'gateway']),
        ];

        echo 'get ips';

        $this->cloudinitService->updateIpConfig($ipconfig);
        echo 'updated cloudinit';
        $this->networkService->lockIps(Arr::flatten($this->server->addresses()->get(['address'])->toArray()));

        /* 7. Kill the server to guarantee configurations are active */
        $this->powerRepository->send('stop');
    }

    public function oldreinstall(Server $template)
    {
        $originalServer = clone $this->server;
        $instantiatedResourceService = (clone $this->resourceService)->setServer($originalServer);
        $instantiatedNetworkService = (clone $this->networkService)->setServer($originalServer);
        $instantiatedCloudinitService = (clone $this->cloudinitService)->setServer($originalServer);

        $ipconfigResponse = $instantiatedCloudinitService->getIpConfig();

        $originalResources = [
            'disks' => $instantiatedResourceService->getDisks(),
            'resources' => $instantiatedResourceService->getResources(),
            'bootOrder' => $instantiatedResourceService->getBootOrder(),
            'ipconfig' => $ipconfigResponse['pending'] ?? $ipconfigResponse['value'] ?? [],
            'ipsets' => [],
        ];

        $originalSpecifications = [
            'cores' => $originalResources['resources']['maxcpu'],
            'memory' => $originalResources['resources']['maxmem'] / 1024 / 1024,
        ];

        // get all ipsets (underscore indicates it must not be referenced elsewhere because it's temporary)
        $_ipSets = array_column($instantiatedNetworkService->getIpsets(), 'name');

        foreach ($_ipSets as $ipSet) {
            $lockedIps = array_column($instantiatedNetworkService->getLockedIps($ipSet), 'cidr');

            array_push($originalResources['ipsets'], [
                'name' => $ipSet,
                'addresses' => $lockedIps,
            ]);
        }


        $templateDisks = (clone $this->resourceService)->setServer($template)->getDisks();



        // get all the IPsets


        (clone $this->powerService)->setServer($originalServer)->kill();

        $this->delete();

        $this->setServer($template)->install($originalServer->vmid, $this->node->cluster);

        $instantiatedResourceService->setCores($originalSpecifications['cores']);
        $instantiatedResourceService->setMemory($originalSpecifications['memory']);

        // time to migrate the disks

        $newDisks = [];

        // If the template has no disks. I don't want to get stuck in an infinite loop waiting for a disk to appear.
        if (count($templateDisks) > 0) {
            do {
                $newDisks = $instantiatedResourceService->getDisks();
            } while (count($newDisks) === 0);
        }

        $instantiatedResourceService->updateDisks($originalResources['disks'], $newDisks);

        // set boot $order

        $instantiatedResourceService->setBootOrder($originalResources['bootOrder']['raw']);

        // set IP sets
        foreach ($originalResources['ipsets'] as $ipSet)
        {
            $instantiatedNetworkService->createIpset($ipSet['name']);

            foreach ($ipSet['addresses'] as $address)
            {
                $instantiatedNetworkService->lockIp($ipSet['name'], $address);
            }
        }

        // update cloudinit ip
        $instantiatedCloudinitService->updateIpConfig($originalResources['ipconfig']);

        // apply the changes
        $this->powerService->setServer($originalServer)->kill();

        return true;
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
