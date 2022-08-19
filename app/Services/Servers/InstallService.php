<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\ProxmoxService;
use Exception;
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
    private ServerUpdateService $updateService;

    public function __construct()
    {
        $this->networkService = new NetworkService;
        $this->cloudinitService = new CloudinitService;
        $this->detailService = new ServerDetailService;
        $this->serverRepository = new ProxmoxServerRepository;
        $this->allocationRepository = new ProxmoxAllocationRepository;
        $this->allocationService = new AllocationService;
        $this->powerRepository = new ProxmoxPowerRepository;
        $this->updateService = new ServerUpdateService;
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

        // Wait for server to fully delete
        $deletionStatus = false;

        do {
            try {
                $this->serverRepository->getStatus(); // if it errors, this indicates the server doesn't exist
            } catch (Exception $e) {
                $deletionStatus = true;
            }
        } while (!$deletionStatus);

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
        $this->updateService->setServer($this->server);

        /* 1. Clone the template */
        $this->serverRepository->create($template->server->vmid);

        // Wait until cloning is complete
        $intermissionDetails = $this->detailService->getDetails();

        if (Arr::get($intermissionDetails, 'locked')) {
            do {
                $intermissionDetails = $this->detailService->getDetails();
            } while (Arr::get($intermissionDetails, 'locked'));
        }

        $this->updateService->handle($details);

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
