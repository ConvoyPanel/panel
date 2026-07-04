<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;

class ServerNetworkBandwidthService
{
    public function __construct(private ProxmoxConfigRepository $configRepository) {}

    /**
     * Sets the network bandwidth rate limit for all network devices on the given server.
     *
     * Updates only devices that do not already have the specified rate limit.
     *
     * @param  Server  $server  The server whose network devices will be updated.
     * @param  int  $rate  The new rate limit in bytes per second.
     *
     * @throws RequestException
     */
    public function setRateLimit(Server $server, int $rate): void
    {
        $config = $this->configRepository->setServer($server)->getConfig();

        /** @var array<string, string> $networkDevices */
        $networkDevices = $config->networkDevices
            ->filter(function (NetworkDeviceData $device) use ($rate) {
                // Skip devices that already have the desired rate limit
                return $device->rateLimit !== $rate;
            })
            ->map(function (NetworkDeviceData $device) use ($rate) {
                $device->rateLimit = $rate;

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
     * Removes the network bandwidth rate limit for all network devices on the given server.
     *
     * @throws RequestException
     */
    public function removeRateLimit(Server $server): void
    {
        $config = $this->configRepository->setServer($server)->getConfig();

        /** @var array<string, string> $networkDevices */
        $networkDevices = $config->networkDevices
            ->filter(function (NetworkDeviceData $device) {
                // Skip devices that do not have a rate limit set
                return ! is_null($device->rateLimit);
            })
            ->map(function (NetworkDeviceData $device) {
                $device->rateLimit = null; // Remove rate limit

                return $device->toProxmoxString();
            })
            ->reduce(function (array $carry, array $item) {
                [$id, $config] = $item;
                $carry[$id] = $config;

                return $carry;
            }, []);

        $this->configRepository->update($networkDevices, $config->digest);
    }
}
