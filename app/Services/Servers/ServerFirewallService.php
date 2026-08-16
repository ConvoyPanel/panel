<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Network\IpsetData;
use App\Data\Server\Proxmox\Network\LockedIpData;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxFirewallClient;

class ServerFirewallService
{
    public function __construct(
        private ProxmoxFirewallClient $firewallClient,
    ) {}

    /**
     * Automatically configures the firewall options for IP address management.
     *
     * Deliberately limited to the two options the platform guarantees: the
     * firewall being on at all, and ipfilter, which is what makes the
     * per-interface anti-spoof ipsets below actually bind. The default
     * policies are NOT set here -- they belong to the user, and this method
     * runs on every network sync (address changes, rebuilds, VM syncs), so
     * writing them would silently revert a user's default-deny posture the
     * next time any of those happened.
     *
     * @throws RequestException
     */
    public function configureFirewall(Server $server): void
    {
        $this->firewallClient->setServer($server)->updateOptions([
            'enable' => true,
            'ipfilter' => true,
        ]);
    }

    /**
     * Deletes an IP set and unlocks all IP addresses associated with it.
     *
     * @throws RequestException
     */
    public function deleteIpset(Server $server, string|IpsetData $ipset): void
    {
        $this->firewallClient->setServer($server);

        $this
            ->firewallClient
            ->getLockedIps($ipset)
            ->each(function (LockedIpData $lockedIp) use ($ipset) {
                $this->firewallClient->unlockIp($ipset, $lockedIp);
            });

        $this->firewallClient->deleteIpset($ipset);
    }

    /**
     * Clears all IP sets and unlocks all IP addresses associated with them.
     *
     * @throws RequestException
     */
    public function clearIpsets(Server $server): void
    {
        $this->firewallClient->setServer($server);

        $this
            ->firewallClient
            ->getIpsets()
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

        $this->firewallClient->setServer($server);

        $this->firewallClient->createIpset($ipset);

        foreach ($addresses as $address) {
            $this->firewallClient->lockIp($ipset, $address);
        }
    }
}
