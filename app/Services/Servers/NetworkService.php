<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Exceptions\Service\Network\AddressInUseException;
use Convoy\Models\IPAddress;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class NetworkService extends ProxmoxService
{
    public function __construct(private ProxmoxAllocationRepository $allocationRepository, private ConnectionInterface $connection, private ServerDetailService $detailService)
    {
    }

    public function deleteIpset(string $name)
    {
        $this->allocationRepository->setServer($this->server);

        $addresses = array_column($this->allocationRepository->getLockedIps($name), 'cidr');

        foreach ($addresses as $address) {
            $this->allocationRepository->unlockIp($name, $address);
        }

        return $this->allocationRepository->setServer($this->server)->deleteIpset($name);
    }

    public function clearIpsets()
    {
        $this->allocationRepository->setServer($this->server);

        $ipSets = array_column($this->allocationRepository->getIpsets(), 'name');

        foreach ($ipSets as $ipSet) {
            $this->deleteIpset($ipSet);
        }
    }

    public function lockIps(array $addresses, string $ipsetName = 'default')
    {
        $this->allocationRepository->setServer($this->server);

        $this->allocationRepository->createIpset($ipsetName);

        foreach ($addresses as $address) {
            $this->allocationRepository->lockIp($ipsetName, $address);
        }
    }

    public function getPrimaryMacAddress()
    {
        $details = $this->detailService->setServer($this->server)->getDetails();

        return $details->config->mac_address;
    }

    public function syncSettings()
    {
        $macAddress = $this->getPrimaryMacAddress();

        return $this->allocationRepository->setServer($this->server)->update(['net0' => "virtio={$macAddress},bridge={$this->node->network}"]);
    }

    public function updateRateLimit(?int $mebibytes = null)
    {
        $macAddress = $this->getPrimaryMacAddress();

        $payload = "virtio={$macAddress},bridge={$this->node->network}";

        if (!is_null($mebibytes))
            $payload .= ',rate=' . $mebibytes;

        return $this->allocationRepository->setServer($this->server)->update(['net0' => $payload]);
    }

    public function updateAddresses(array $addressIds, bool $force = false)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->connection->transaction(function () use ($addressIds, $force) {
            $existingAddresses = $this->server->addresses;

            // make diff of addresses to remove
            foreach ($existingAddresses as $address) {
                if (!in_array($address->id, $addressIds)) {
                    $address->update(['server_id' => null]);
                }
            }

            // add the new addresses from the $addressIds
            $addressesToAdd = IPAddress::whereIn('id', array_diff($addressIds, $existingAddresses->pluck('id')->toArray()))->get();

            $addressesToAdd->each(function (IPAddress $address) use ($force) {
                // This checks if an address is already binded to another server. We don't want to update another server on accident.
                if ($address->server_id !== null && $address->server_id !== $this->server->id) {

                    // if the developer specified to use force, welp we're going to update the unsuspecting server.
                    if ($force) {
                        $address->update(['server_id' => $this->server->id]);
                    } else {
                        throw new AddressInUseException($address->id);
                    }
                } else {
                    $address->update(['server_id' => $this->server->id]);
                }
            });
        });
    }

    /**
     * @deprecated This is being deprecated in favor of the new updateAddresses method.
     * @param  array<int, int>  $addressIds
     */
    public function convertFromEloquent(array $addressIds): array
    {
        $addresses = [
            'ipv4' => [],
            'ipv6' => [],
        ];

        Arr::map($addressIds, function ($address_id) use (&$addresses) {
            $address = IPAddress::find($address_id);
            $type = AddressType::from($address->type)->value;

            $addresses[$type][] = [
                'address' => $address->address,
                'cidr' => $address->cidr,
                'gateway' => $address->gateway,
                'mac_address' => $address->mac_address,
            ];
        });

        return $addresses;
    }
}
