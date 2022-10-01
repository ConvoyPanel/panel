<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;

class NetworkService extends ProxmoxService
{

    public function __construct(protected ProxmoxAllocationRepository $allocationRepository)
    {}

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

    public function updateMacAddress(string $address)
    {
        return $this->allocationRepository->setServer($this->server)->update(['net0' => "virtio={$address}"]);
    }

    public function syncNetworkDeviceSettings()
    {
        return $this->allocationRepository->setServer($this->server)->update(['net0' => "bridge={$this->node->network}"], put: true);
    }

    /**
     * @param array<int, int> $addressIds
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
                    'mac_address' => $address->mac_address
                ];
            });

        return $addresses;
    }
}