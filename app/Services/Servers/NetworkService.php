<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Eloquent\ServerAddressesData;
use Convoy\Data\Server\MacAddressData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxFirewallRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class NetworkService
{
    public function __construct(private ProxmoxFirewallRepository $firewallRepository, private CloudinitService $cloudinitService, private ProxmoxCloudinitRepository $cloudinitRepository, private ProxmoxConfigRepository $allocationRepository, private ConnectionInterface $connection)
    {
    }

    public function deleteIpset(Server $server, string $name)
    {
        $this->firewallRepository->setServer($server);

        $addresses = array_column($this->firewallRepository->getLockedIps($name), 'cidr');

        foreach ($addresses as $address) {
            $this->firewallRepository->unlockIp($name, $address);
        }

        return $this->firewallRepository->deleteIpset($name);
    }

    public function clearIpsets(Server $server)
    {
        $this->firewallRepository->setServer($server);

        $ipSets = array_column($this->firewallRepository->getIpsets(), 'name');

        foreach ($ipSets as $ipSet) {
            $this->deleteIpset($server, $ipSet);
        }
    }

    public function lockIps(Server $server, array $addresses, string $ipsetName = 'default')
    {
        $this->firewallRepository->setServer($server);

        $this->firewallRepository->createIpset($ipsetName);

        foreach ($addresses as $address) {
            $this->firewallRepository->lockIp($ipsetName, $address);
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
        $macAddresses = $this->getMacAddresses($server, true, true);
        $addresses = $this->getAddresses($server);

        $this->clearIpsets($server);
        $this->cloudinitService->updateIpConfig($server, CloudinitAddressConfigData::from([
            'ipv4' => $addresses->ipv4->first()?->toArray(),
            'ipv6' => $addresses->ipv6->first()?->toArray(),
        ]));
        $this->lockIps($server, array_unique(Arr::flatten($server->addresses()->get(['address'])->toArray())));
        $this->firewallRepository->setServer($server)->updateOptions([
            'ipfilter' => true,
            'policy_in' => 'ACCEPT',
            'policy_out' => 'ACCEPT',
        ]);

        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $this->allocationRepository->setServer($server)->update(['net0' => "virtio={$macAddress},bridge={$server->node->network}"]);
    }

    public function updateRateLimit(Server $server, ?int $mebibytes = null)
    {
        $macAddresses = $this->getMacAddresses($server, true, true);
        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $payload = "virtio={$macAddress},bridge={$server->node->network}";

        if (!is_null($mebibytes))
            $payload .= ',rate=' . $mebibytes;

        $this->allocationRepository->setServer($server)->update(['net0' => $payload]);
    }

    public function updateAddresses(Server $server, array $addressIds)
    {
        $currentAddresses = $server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter($currentAddresses, fn($id) => !in_array($id, $addressIds));

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
