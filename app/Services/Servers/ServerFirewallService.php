<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Network\IpsetData;
use App\Data\Server\Proxmox\Network\LockedIpData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxFirewallRepository;

class ServerFirewallService
{
    public function __construct(private ProxmoxFirewallRepository $repository) {}

    /**
     * Automatically configures the firewall options for IP address management.
     *
     * @throws RequestException
     */
    public function configureFirewall(Server $server): void
    {
        $this->repository->setServer($server)->updateOptions([
            'enable' => true,
            'ipfilter' => true,
            'policy_in' => 'ACCEPT',
            'policy_out' => 'ACCEPT',
        ]);
    }

    /**
     * Deletes an IP set and unlocks all IP addresses associated with it.
     *
     * @throws RequestException
     */
    public function deleteIpset(Server $server, string|IpsetData $ipset): void
    {
        $this->repository->setServer($server);

        $this
            ->repository
            ->getLockedIps($ipset)
            ->toCollection()
            ->each(function (LockedIpData $lockedIp) use ($ipset) {
                $this->repository->unlockIp($ipset, $lockedIp);
            });

        $this->repository->deleteIpset($ipset);
    }

    /**
     * Clears all IP sets and unlocks all IP addresses associated with them.
     *
     * @throws RequestException
     */
    public function clearIpsets(Server $server): void
    {
        $this->repository->setServer($server);

        $this
            ->repository
            ->getIpsets()
            ->toCollection()
            ->each(function (IpsetData $ipset) use ($server) {
                $this->deleteIpset($server, $ipset);
            });
    }

    /**
     * Locks the specified IP addresses in the given IP set.
     *
     * @throws RequestException
     */
    public function lockIps(Server $server, array $addresses, string|IpsetData $ipset): void
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        $this->repository->setServer($server);

        $this->repository->createIpset($ipset);

        foreach ($addresses as $address) {
            $this->repository->lockIp($ipset, $address);
        }
    }
}
