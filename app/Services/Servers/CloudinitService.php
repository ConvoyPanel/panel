<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\IpConfigData;
use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Address;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Arr;

use function collect;
use function explode;

/**
 * Service for managing cloud-init configurations in Proxmox servers.
 *
 * @see https://pve.proxmox.com/pve-docs/api-viewer/#/nodes/%7Bnode%7D/qemu/%7Bvmid%7D/config
 */
class CloudinitService
{
    public function __construct(private ProxmoxConfigRepository $configRepository) {}

    /**
     * Sets the hostname and search domain for a server in Proxmox.
     *
     * @throws RequestException
     */
    public function setHostname(Server $server, string $hostname): void
    {
        $config = $this->configRepository->setServer($server)->getConfig();

        // Write only what differs, so an unchanged hostname doesn't enqueue a
        // redundant Proxmox "Configure" task.
        $payload = [];
        if ($config->name !== $hostname) {
            $payload['name'] = $hostname;
        }
        if ($config->cloudinit->searchDomain !== $hostname) {
            $payload['searchdomain'] = $hostname;
        }

        if ($payload === []) {
            return;
        }

        $this->configRepository->setServer($server)->update($payload);
    }

    /**
     * @return string[]
     *
     * @throws RequestException
     */
    public function getNameservers(Server $server): array
    {
        $nameservers = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'nameserver')->first();

        return $nameservers ? explode(' ', $nameservers['value']) : [];
    }

    public function setNameservers(Server $server, array $nameservers): void
    {
        $payload = [
            ...(count($nameservers) > 0 ? ['nameserver' => implode(' ', $nameservers)] : []),
            ...(count($nameservers) === 0 ? ['delete' => 'nameserver'] : []),
        ];

        $this->configRepository->setServer($server)->update($payload);
    }

    /**
     * Updates the IP configuration for a server in Proxmox.
     * Configures IPv4 and IPv6 addresses with their respective gateways.
     *
     * @throws RequestException
     */
    public function setIpConfig(Server $server, ?Address $ipv4, ?Address $ipv6): void
    {
        $payload = [];

        if ($ipv4) {
            $payload[] = "ip={$ipv4->ip}/{$ipv4->prefix_length}";
            $payload[] = 'gw='.$ipv4->gateway;
        }

        if ($ipv6) {
            $payload[] = "ip6={$ipv6->ip}/{$ipv6->prefix_length}";
            $payload[] = 'gw6='.$ipv6->gateway;
        }

        $payload = Arr::join($payload, ',');

        // The set of ipconfig keys is derived from the NICs we just read, so
        // guard the write with that read's digest (optimistic concurrency).
        $config = $this->configRepository->setServer($server)->getConfig();

        // Parse our own desired string through the same codec as the stored config,
        // so the comparison is order/format-insensitive, and skip NICs that are
        // already at the target ipconfig (avoids a redundant Configure task).
        $desired = IpConfigData::fromString($payload);

        /** @var array<string, string> $networkDevices */
        $networkDevices = $config->networkDevices
            ->filter(function (NetworkDeviceData $device) use ($config, $desired) {
                $current = $config->cloudinit->ipConfigs->get($device->id);

                return $current === null || $current->toArray() !== $desired->toArray();
            })
            ->mapWithKeys(fn (NetworkDeviceData $device) => ["ipconfig$device->id" => $payload])
            ->all();

        if ($networkDevices === []) {
            return;
        }

        $this->configRepository->update($networkDevices, $config->digest);
    }
}
