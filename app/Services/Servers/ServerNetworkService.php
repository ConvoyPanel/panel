<?php

namespace App\Services\Servers;

use App\Data\Server\Eloquent\PrimaryAddressesData;
use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Enums\Network\AddressState;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Address;
use App\Models\NetworkInterface;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;

use function array_unique;

class ServerNetworkService
{
    public function __construct(
        private ServerFirewallService $firewallService,
        private CloudinitService $cloudinitService,
        private ProxmoxConfigRepository $configRepository,
    ) {}

    /**
     * @throws RequestException
     */
    public function syncSettings(Server $server): void
    {
        $this->firewallService->configureFirewall($server);
        $this->firewallService->clearIpsets($server);
        $this->lockServerAddresses($server);

        $this->syncCloudinitIpConfig($server);

        $this->syncNetworkDeviceConfig($server);
    }

    /**
     * @throws RequestException
     */
    private function syncNetworkDeviceConfig(Server $server): void
    {
        $primaryAddresses = $this->getPrimaryAddresses($server);

        $ipv4 = $primaryAddresses->ipv4;
        $ipv6 = $primaryAddresses->ipv6;
        $macAddress = ($ipv4 instanceof Address ? $ipv4->mac_address : null)
            ?? ($ipv6 instanceof Address ? $ipv6->mac_address : null);
        $ipv4Interface = $ipv4?->networkInterfaces()->first();
        $ipv6Interface = $ipv6?->networkInterfaces()->first();
        $bridge = ($ipv4Interface instanceof NetworkInterface ? $ipv4Interface->name : null)
            ?? ($ipv6Interface instanceof NetworkInterface ? $ipv6Interface->name : null);

        $config = $this->configRepository->setServer($server)->getConfig();

        // Skip NICs already in the desired state so we don't rewrite them.
        // Firewall isn't the only field we set here — a NIC could be firewalled
        // but still need its mac/bridge corrected — so a device is only
        // redundant when all three would be unchanged.
        $devicesToUpdate = $config->networkDevices
            ->filter(function (NetworkDeviceData $device) use ($macAddress, $bridge) {
                $needsFirewall = $device->isFirewallEnabled !== true;
                $needsMac = $macAddress !== null && $macAddress !== $device->macAddress;
                $needsBridge = $bridge !== null && $bridge !== $device->bridge;

                return $needsFirewall || $needsMac || $needsBridge;
            });

        // Nothing to change — skip the write entirely rather than POST an
        // empty (digest-only) config update.
        if ($devicesToUpdate->isEmpty()) {
            return;
        }

        $networkDevices = $devicesToUpdate
            ->map(function (NetworkDeviceData $device) use ($macAddress, $bridge) {
                $device->isFirewallEnabled = true;
                $device->macAddress = $macAddress ?? $device->macAddress;
                $device->bridge = $bridge ?? $device->bridge;

                return $device->toProxmoxString();
            })
            ->reduce(function (array $carry, array $item) {
                [$id, $config] = $item;
                $carry[$id] = $config;

                return $carry;
            }, []);

        $this->configRepository->update($networkDevices, $config->digest);
    }

    /**
     * @throws RequestException
     */
    private function syncCloudinitIpConfig(Server $server): void
    {
        $primaryAddresses = $this->getPrimaryAddresses($server);

        $this->cloudinitService->setIpConfig($server, $primaryAddresses->ipv4, $primaryAddresses->ipv6);
    }

    /**
     * @throws RequestException
     */
    private function lockServerAddresses(Server $server): void
    {
        $addresses = array_unique($server->addresses()->pluck('ip')->all());

        $this->configRepository
            ->setServer($server)
            ->getConfig()
            ->networkDevices
            ->each(
                /**
                 * @throws RequestException
                 */
                function (NetworkDeviceData $device) use ($server, $addresses) {
                    [$deviceId, $_] = $device->toProxmoxString();
                    $this->firewallService->lockIps(
                        $server,
                        $addresses,
                        "ipfilter-$deviceId",
                    );
                });
    }

    public function getPrimaryAddresses(Server $server): PrimaryAddressesData
    {
        return new PrimaryAddressesData(
            ipv4: $server->primaryIPv4Address ?? $server->addresses()
                ->whereHas('addressBlock', fn ($query) => $query->where('version', 'ipv4'))
                ->first(),
            ipv6: $server->primaryIPv6Address ?? $server->addresses()
                ->whereHas('addressBlock', fn ($query) => $query->where('version', 'ipv6'))
                ->first(),
        );
    }

    /**
     * Allocates the addresses to the server. Changes do not fully activate without running syncSettings().
     * Also, you better fucking make sure that the addresses are accessible to the server's node.
     *
     * @param int[]|Address[] $addresses
     */
    public function syncAddresses(Server $server, array $addresses): void
    {
        // Normalize input: get array of address IDs
        $addressIds = collect($addresses)->map(function ($address) {
            return $address instanceof Address ? $address->id : $address;
        })->unique()->values()->all();

        // Get current address IDs attached to this server
        $currentAddresses = $server->addresses()->pluck('id')->toArray();

        // Determine which addresses to add and remove
        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_diff($currentAddresses, $addressIds);

        // Attach new addresses. The `state = available` guard means a reserved address is never
        // silently assigned (reserved is fully locked), and it flips available -> assigned.
        if (!empty($addressesToAdd)) {
            Address::query()
                ->where('state', AddressState::Available)
                ->whereIn('id', $addressesToAdd)
                ->update(['server_id' => $server->id, 'state' => AddressState::Assigned]);
        }

        // Detach addresses no longer associated (assigned -> available).
        if (!empty($addressesToRemove)) {
            Address::query()
                ->where('server_id', $server->id)
                ->whereIn('id', $addressesToRemove)
                ->update(['server_id' => null, 'state' => AddressState::Available]);
        }
    }
}
