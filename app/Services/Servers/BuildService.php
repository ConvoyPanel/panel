<?php

namespace Convoy\Services\Servers;

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
        protected ServerDetailService $detailService,
        protected ProxmoxServerRepository $serverRepository,
        protected ProxmoxPowerRepository $powerRepository,
        protected ServerUpdateService $updateService
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
        $this->powerRepository->send('stop');

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

        $this->updateService->handle(ServerSpecificationsObject::from($intermissionDetails->toArray()));
        LogRunner::setActivity($activity)->end();

        return $this->detailService->getDetails();
    }

    public function rebuild(Template $template)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->delete();

        return $this->build($template);
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
