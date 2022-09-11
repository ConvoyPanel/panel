<?php

namespace App\Services\Servers;

use App\Enums\Network\AddressType;
use App\Models\IPAddress;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

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

    public function validateForDuplicates(int $server_id, string $type, int|null $address_id = null)
    {
        $existingAddress = Server::find($server_id)->addresses()->where('type', AddressType::from($type)->value)->first();

        if ($existingAddress !== null && $existingAddress->server_id === $server_id && $existingAddress->id !== $address_id)
        {
            throw ValidationException::withMessages([
                'server_id' => "This server already has an {$type} address."
            ]);
        }
    }

    /**
     * @param array<int, int> $addressIds
     */
    public function convertFromEloquent(array $addressIds): array
    {
        $addresses = [
            'ipv4' => null,
            'ipv6' => null,
        ];

            Arr::map($addressIds, function ($address_id) use (&$addresses) {
                $address = IPAddress::find($address_id);
                $type = AddressType::from($address->type)->value;

                if (isset($addresses[$type]))
                    throw ValidationException::withMessages([
                        'addresses' => 'You cannot set multiple IPv4 or IPv6 addresses'
                    ]);

                if (isset($address->server_id))
                    throw ValidationException::withMessages([
                        'addresses' => 'This address is actively being used',
                    ]);

                $addresses[$type] = [
                    'address' => $address->address,
                    'cidr' => $address->cidr,
                    'gateway' => $address->gateway,
                ];

                if ($type === AddressType::IPV4->value)
                    $addresses[$type]['mac_address'] = $address->mac_address;
            });

        return $addresses;
    }
}