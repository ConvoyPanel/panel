<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;

class NetworkService extends ProxmoxService
{
    public function __construct(private ProxmoxAllocationRepository $allocationRepository, private ServerDetailService $detailService)
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

    /**
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
