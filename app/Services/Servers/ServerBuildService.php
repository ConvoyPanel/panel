<?php

namespace Convoy\Services\Servers;

use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Class SnapshotService
 */
class ServerBuildService
{
    public function __construct(
        private ProxmoxConfigRepository $configRepository,
        private ProxmoxServerRepository $serverRepository,
    ) {
    }

    public function delete(Server $server)
    {
        $this->serverRepository->setServer($server)->delete();
    }

    public function build(Server $server, Template $template)
    {
        $this->serverRepository->setServer($server)->create($template);
    }

    public function isVmCreated(Server $server): bool
    {
        try {
            $config = collect($this->configRepository->setServer($server)->getConfig());

            $lock = $config->where('key', '=', 'lock')->first();

            if ($lock && ($lock['value'] === 'clone' || $lock['value'] === 'create')) {
                return false;
            }
        } catch (ProxmoxConnectionException $e) {
            return false;
        }

        return true;
    }

    public function isVmDeleted(Server $server): bool
    {
        try {
            $this->configRepository->setServer($server)->getConfig();
        } catch (ProxmoxConnectionException $e) {
            return true;
        }

        return false;
    }

    /**
     * Whether the server's VM exists on its node.
     *
     * Unlike isVmDeleted(), only Proxmox's own "does not exist" answer counts as
     * missing. Any other failure -- the node unreachable, a bad token, a 5xx --
     * is rethrown, so an outage can never be mistaken for a VM that's gone and
     * lead to a server being dropped from Convoy while its VM keeps running.
     *
     * @throws ProxmoxConnectionException
     */
    public function vmExists(Server $server): bool
    {
        try {
            $this->configRepository->setServer($server)->getConfig();
        } catch (ProxmoxConnectionException $e) {
            if (self::isVmMissingError($e)) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * Proxmox answers any request about a VMID it doesn't have with a 500 and
     * "Configuration file 'nodes/<node>/qemu-server/<vmid>.conf' does not exist".
     */
    public static function isVmMissingError(\Throwable $e): bool
    {
        $request = $e->getPrevious();

        return $request instanceof RequestException
            && $request->response->status() === 500
            && str_contains((string) $request->response->json('message'), 'does not exist');
    }
}
