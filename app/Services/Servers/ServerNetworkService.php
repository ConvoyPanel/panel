<?php

namespace App\Services\Servers;

use App\Data\Server\Eloquent\PrimaryAddressesData;
use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Address;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Arr;

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

        /** @var string|null $macAddress */
        $macAddress = $primaryAddresses->ipv4?->mac_address ?? $primaryAddresses->ipv6?->mac_address;
        /** @var string|null $bridge */
        $bridge = $primaryAddresses->ipv4?->networkInterfaces()->first()?->name ?? $primaryAddresses->ipv6?->networkInterfaces()->first()?->name;

        $networkDevices = $this->configRepository
            ->setServer($server)
            ->getConfig()
            ->networkDevices
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

        $this->configRepository->update($networkDevices);
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
        $addresses = array_unique(Arr::flatten($server->addresses()->get('ip')->toArray()));

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
            ipv4: $server->primaryIPv4Address ?? $server->addresses()->withIPv4()->first(),
            ipv6: $server->primaryIPv6Address ?? $server->addresses()->withIPv6()->first(),
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

        // Attach new addresses
        if (!empty($addressesToAdd)) {
            Address::query()
                ->whereNull('server_id')
                ->whereIn('id', $addressesToAdd)
                ->update(['server_id' => $server->id]);
        }

        // Detach addresses no longer associated
        if (!empty($addressesToRemove)) {
            Address::query()
                ->where('server_id', $server->id)
                ->whereIn('id', $addressesToRemove)
                ->update(['server_id' => null]);
        }
    }
}
