<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Cloudinit\AuthenticationType;
use Convoy\Enums\Server\Power;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
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
class ServerBuildService extends ProxmoxService
{
    public function __construct(
        private ServerDetailService $detailService,
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxPowerRepository $powerRepository,
        private BuildModificationService $buildModificationService,
        private CloudinitService $cloudinitService,
    ) {
    }

    public function delete(Server $server)
    {
        /* 1. Power off the server */
        try {
            $this->powerRepository->setServer($server)->send(Power::KILL);
        } catch (\Exception $e) {
            // do nothing.
        }

        // Wait for server to turn off
        $intermissionStatus = $this->serverRepository->setServer($server)->getStatus();

        if (Arr::get($intermissionStatus, 'status') !== 'stopped') {
            do {
                $intermissionStatus = $this->serverRepository->getStatus();
            } while (Arr::get($intermissionStatus, 'status') !== 'stopped');
        }

        /* 3. Delete the server */
        $this->serverRepository->delete();

        // Wait for server to fully delete
        $deleted = false;

        do {
            try {
                $this->serverRepository->getStatus(); // if it errors, this indicates the server doesn't exist

                sleep(1);
            } catch (Exception $e) {
                $deleted = true;
            }
        } while (!$deleted);
    }

    public function build(ServerDeploymentData $deployment)
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

        $this->powerRepository->setServer($deployment->server);

        if ($deployment->should_create_server) {
            /* 1. Clone the template */
            $this->serverRepository->setServer($deployment->server)->create($deployment->template);

            // Wait until cloning is complete
            $intermissionDetails = null;

            do {
                try {
                    $intermissionDetails = $this->detailService->getByProxmox($deployment->server);
                } catch (\Throwable $e) {
                    $intermissionDetails = null;
                }
            } while (empty($intermissionDetails) || $intermissionDetails->locked);
        }

        if(!empty($deployment->account_password)) {
            $this->cloudinitService->setServer($deployment->server)->changePassword($deployment->account_password, AuthenticationType::PASSWORD);
        }

        $this->runUpdate($this->buildModificationService, $deployment);

        if ($deployment->start_on_completion) {
            $this->powerRepository->send(Power::START);
        }
    }

    private function runUpdate(BuildModificationService $service, ServerDeploymentData $deployment)
    {
        try {
            $service->handle($deployment->server);
        } catch (ProxmoxConnectionException $e) {
            // for some fucking reason, Proxmox once in a while throws this stupid error. Proxmox can eat it while I retry the whole thing again
            $fail = (bool) preg_match("/atomic file '\/var\/log\/pve\/tasks\/active' failed: No such file or directory/", $e->getMessage());

            if ($fail) {
                sleep(1);

                $this->runUpdate($service, $deployment);
            } else {
                throw $e;
            }
        }
    }

    public function rebuild(ServerDeploymentData $deployment)
    {
        $this->delete($deployment->server);

        $this->build($deployment);
    }
}
