<?php

namespace Convoy\Services\Servers;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Facades\Activity;
use Convoy\Facades\LogRunner;
use Convoy\Models\Objects\Server\ServerSpecificationsObject;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\ProxmoxService;
use Exception;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Class SnapshotService
 */
class BuildService extends ProxmoxService
{
    public function __construct(
        private ServerDetailService $detailService,
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxPowerRepository $powerRepository,
        private ServerUpdateService $updateService
    ) {
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
        try {
            $this->powerRepository->send('stop');
        } catch (\Exception $e) {
            // do nothing.
        }

        // Wait for server to turn off
        $intermissionStatus = $this->serverRepository->getStatus();

        if (Arr::get($intermissionStatus, 'status') !== 'stopped') {
            do {
                $intermissionStatus = $this->serverRepository->getStatus();
            } while (Arr::get($intermissionStatus, 'status') !== 'stopped');
        }

        /* 3. Delete the server */
        $upid = $this->serverRepository->delete();
        $activity = Activity::event('server:uninstall')->runner($upid)->log();

        // Wait for server to fully delete
        $deletionStatus = false;

        do {
            try {
                $this->serverRepository->getStatus(); // if it errors, this indicates the server doesn't exist
            } catch (Exception $e) {
                $deletionStatus = true;
            }
        } while (! $deletionStatus);

        LogRunner::setActivity($activity)->end();

        /* 4. Return the server details */
        return $details;
    }

    public function build(Template $template)
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
        $this->detailService->setServer($this->server);
        $this->powerRepository->setServer($this->server);
        $this->serverRepository->setServer($this->server);
        $this->updateService->setServer($this->server);

        /* 1. Clone the template */
        $upid = $this->serverRepository->create($template->server->vmid);
        $activity = Activity::event('server:install')->runner($upid)->log();

        // Wait until cloning is complete
        $intermissionDetails = null;

        do {
            try {
                $intermissionDetails = $this->detailService->getDetails();
            } catch (\Throwable $e) {
                $intermissionDetails = null;
            }
        } while (empty($intermissionDetails) || $intermissionDetails->locked);

        LogRunner::setActivity($activity)->end();

        $activity = Activity::event('server:details.update')->runner()->log();

        function runUpdate(ServerUpdateService $service) {
            try {
                $service->handle();
            } catch (ProxmoxConnectionException $e) {
                // for some fucking reason, Proxmox once in a while throws this stupid error. Proxmox can eat it while I retry the whole thing again
                $fail = (bool) preg_match("/atomic file '\/var\/log\/pve\/tasks\/active' failed: No such file or directory/", $e->getMessage());

                if ($fail) {
                    sleep(1);

                    runUpdate($service);
                } else {
                    throw $e;
                }
            }
        };

        runUpdate($this->updateService);

        LogRunner::setActivity($activity)->end();

        return $this->detailService->getDetails();
    }

    public function rebuild(Template $template)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->delete();

        return $this->build($template);
    }
}
