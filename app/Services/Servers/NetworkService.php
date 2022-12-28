<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Eloquent\ServerAddressesData;
use Convoy\Data\Server\Proxmox\ServerProxmoxData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class NetworkService extends ProxmoxService
{
    public function __construct(private CloudinitService $cloudinitService,private ProxmoxAllocationRepository $allocationRepository, private ConnectionInterface $connection, private ServerDetailService $detailService)
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

    public function getPrimaryMacAddress(Server $server)
    {
        $details = $this->detailService->getByProxmox($server);

        return $details->limits->mac_address ?? $details->config->mac_address;
    }

    public function getAddresses(Server $server): ServerAddressesData
    {
        $server = $server->loadMissing('addresses');

        return ServerAddressesData::from([
            'ipv4' => $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
            'ipv6' => $server->addresses->where('type', AddressType::IPV6->value)->toArray()
        ]);
    }

    public function syncSettings(Server $server, ServerProxmoxData $deployment)
    {
        $macAddress = $this->getPrimaryMacAddress($server);

        $this->clearIpsets();
        $this->cloudinitService->updateIpConfig($server, CloudinitAddressConfigData::from([
            'ipv4' => $deployment->limits->addresses->ipv4->first()?->toArray(),
            'ipv6' => $deployment->limits->addresses->ipv6->first()?->toArray(),
        ]));
        $this->lockIps(Arr::flatten($server->addresses()->get(['address'])->toArray()));

        $this->allocationRepository->setServer($this->server)->update(['net0' => "virtio={$macAddress},bridge={$this->node->network}"]);
    }

    public function updateRateLimit(Server $server, ?int $mebibytes = null)
    {
        $macAddress = $this->getPrimaryMacAddress($server);

        $payload = "virtio={$macAddress},bridge={$this->node->network}";

        if (!is_null($mebibytes))
            $payload .= ',rate=' . $mebibytes;

        return $this->allocationRepository->setServer($server)->update(['net0' => $payload]);
    }

    public function updateAddresses(array $addressIds)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $currentAddresses = $this->server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter($currentAddresses, fn ($id) => !in_array($id, $addressIds));

        if (!empty($addressesToAdd)) {
            IPAddress::query()
                ->where('node_id', $this->server->node_id)
                ->whereIn('id', $addressesToAdd)
                ->whereNull('server_id')
                ->update(['server_id' => $this->server->id]);
        }

        if (!empty($addressesToRemove)) {
            IPAddress::query()
                ->where('server_id', $this->server->id)
                ->whereIn('id', $addressesToRemove)
                ->update(['server_id' => null]);
        }
    }
}
