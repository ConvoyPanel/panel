<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxConfigClient;

class ServerNetworkBandwidthService
{
    public function __construct(private ProxmoxConfigClient $configClient) {}

    /**
     * Drive every network device on the server to a desired rate limit and,
     * optionally, link state — in a single config read + at most one write.
     *
     * Only devices that actually differ are re-emitted, so this is idempotent
     * and skips the PVE write entirely when nothing needs to change.
     *
     * @param  ?int  $rate  Desired NIC rate limit in bytes/s; null clears it (unlimited).
     * @param  ?bool  $linkDown  true = disconnect the NIC, false = ensure connected,
     *                           null = leave the link state untouched.
     *
     * @throws RequestException
     * @throws ConfigModifiedException
     */
    public function apply(Server $server, ?int $rate, ?bool $linkDown = null): void
    {
        $config = $this->configClient->setServer($server)->getConfig();

        /** @var array<string, string> $networkDevices */
        $networkDevices = $config->networkDevices
            ->filter(function (NetworkDeviceData $device) use ($rate, $linkDown) {
                $rateDiffers = $device->rateLimit !== $rate;
                $linkDiffers = $linkDown !== null && (bool) $device->isLinkDown !== $linkDown;

                return $rateDiffers || $linkDiffers;
            })
            ->map(function (NetworkDeviceData $device) use ($rate, $linkDown) {
                $device->rateLimit = $rate;

                if ($linkDown !== null) {
                    // false -> null so link_down is omitted rather than emitted as 0.
                    $device->isLinkDown = $linkDown ?: null;
                }

                return $device->toProxmoxString();
            })
            ->reduce(function (array $carry, array $item) {
                [$id, $config] = $item;
                $carry[$id] = $config;

                return $carry;
            }, []);

        // Nothing to change — don't issue a no-op write against Proxmox.
        if ($networkDevices === []) {
            return;
        }

        $this->configClient->update($networkDevices, $config->digest);
    }

    /**
     * Set the network bandwidth rate limit (bytes/s) on all of the server's NICs,
     * leaving link state untouched.
     *
     * @throws RequestException
     * @throws ConfigModifiedException
     */
    public function setRateLimit(Server $server, int $rate): void
    {
        $this->apply($server, $rate);
    }

    /**
     * Remove the network bandwidth rate limit from all of the server's NICs.
     *
     * @throws RequestException
     * @throws ConfigModifiedException
     */
    public function removeRateLimit(Server $server): void
    {
        $this->apply($server, null);
    }
}
