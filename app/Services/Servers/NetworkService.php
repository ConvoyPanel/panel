<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Eloquent\ServerAddressesData;
use Convoy\Data\Server\MacAddressData;
use Convoy\Data\Server\Proxmox\ServerProxmoxData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class NetworkService extends ProxmoxService
{
    public function __construct(private CloudinitService $cloudinitService, private ProxmoxCloudinitRepository $cloudinitRepository, private ProxmoxAllocationRepository $allocationRepository, private ConnectionInterface $connection)
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

    public function getMacAddresses(Server $server, bool $eloquent = true, bool $proxmox = false)
    {
        if ($eloquent) {
            $addresses = $this->getAddresses($server);

            $eloquentMacAddress = $addresses->ipv4->first()?->mac_address ?? $addresses->ipv6->first()?->mac_address;
        }

        if ($proxmox) {
            $config = $this->cloudinitRepository->setServer($server)->getConfig();

            $proxmoxMacAddress = null;
            if (preg_match("/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su", Arr::get($config, 'net0', ''), $matches)) {
                $proxmoxMacAddress = $matches[0];
            }
        }

        return MacAddressData::from([
            'eloquent' => $eloquentMacAddress ?? null,
            'proxmox' => $proxmoxMacAddress ?? null,
        ]);
    }

    public function getAddresses(Server $server): ServerAddressesData
    {
        return ServerAddressesData::from([
            'ipv4' => $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
            'ipv6' => $server->addresses->where('type', AddressType::IPV6->value)->toArray()
        ]);
    }

    public function syncSettings(Server $server)
    {
        $this->setServer($server);

        $macAddresses = $this->getMacAddresses($server, true, true);
        $addresses = $this->getAddresses($server);

        $this->clearIpsets();
        $this->cloudinitService->updateIpConfig($server, CloudinitAddressConfigData::from([
            'ipv4' => $addresses->ipv4->first()?->toArray(),
            'ipv6' => $addresses->ipv6->first()?->toArray(),
        ]));
        $this->lockIps(Arr::flatten($server->addresses()->get(['address'])->toArray()));

        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $this->allocationRepository->setServer($this->server)->update(['net0' => "virtio={$macAddress},bridge={$this->node->network}"]);
    }

    public function updateRateLimit(Server $server, ?int $mebibytes = null)
    {
        $macAddresses = $this->getMacAddresses($server, true, true);
        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $payload = "virtio={$macAddress},bridge={$this->node->network}";

        if (!is_null($mebibytes))
            $payload .= ',rate=' . $mebibytes;

        return $this->allocationRepository->setServer($server)->update(['net0' => $payload]);
    }

    public function updateAddresses(Server $server, array $addressIds)
    {
        $currentAddresses = $server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter($currentAddresses, fn ($id) => !in_array($id, $addressIds));

        if (!empty($addressesToAdd)) {
            IPAddress::query()
                ->where('node_id', $server->node_id)
                ->whereIn('id', $addressesToAdd)
                ->whereNull('server_id')
                ->update(['server_id' => $server->id]);
        }

        if (!empty($addressesToRemove)) {
            IPAddress::query()
                ->where('server_id', $server->id)
                ->whereIn('id', $addressesToRemove)
                ->update(['server_id' => null]);
        }
    }
}
