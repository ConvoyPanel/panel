<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Eloquent\ServerAddressesData;
use Convoy\Data\Server\MacAddressData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\Address;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\AddressRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxFirewallRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class NetworkService
{
    public function __construct(
        private AddressRepository          $repository,
        private ProxmoxFirewallRepository  $firewallRepository,
        private CloudinitService           $cloudinitService,
        private ProxmoxCloudinitRepository $cloudinitRepository,
        private ProxmoxConfigRepository    $allocationRepository,
        private ConnectionInterface        $connection,
    )
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

    public function lockIps(Server $server, array $addresses, string $ipsetName)
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

            $eloquentMacAddress = $addresses->ipv4->first(
            )?->mac_address ?? $addresses->ipv6->first()?->mac_address;
        }

        if ($proxmox) {
            $config = $this->cloudinitRepository->setServer($server)->getConfig();

            $proxmoxMacAddress = null;
            if (preg_match(
                "/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su",
                Arr::get($config, 'net0', ''), $matches,
            )) {
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
            'ipv4' => array_values(
                $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
            ),
            'ipv6' => array_values(
                $server->addresses->where('type', AddressType::IPV6->value)->toArray(),
            ),
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
        $this->lockIps(
            $server, array_unique(Arr::flatten($server->addresses()->get(['address'])->toArray())),
            'ipfilter-net0',
        );
        $this->firewallRepository->setServer($server)->updateOptions([
            'enable' => true,
            'ipfilter' => true,
            'policy_in' => 'ACCEPT',
            'policy_out' => 'ACCEPT',
        ]);

        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $this->allocationRepository->setServer($server)->update(
            ['net0' => "virtio={$macAddress},bridge={$server->node->network},firewall=1"],
        );
    }

    public function updateRateLimit(Server $server, ?int $mebibytes = null)
    {
        $macAddresses = $this->getMacAddresses($server, true, true);
        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $payload = "virtio={$macAddress},bridge={$server->node->network},firewall=1";

        if (!is_null($mebibytes)) {
            $payload .= ',rate=' . $mebibytes;
        }

        $rawConfig = collect($this->allocationRepository->setServer($server)->getConfig())->where('key', '=', 'net0')->first()['value'] ?? null;

        if ($rawConfig !== null && strpos($rawConfig, "rate") !== false) {
            $this->allocationRepository->setServer($server)->update(['net0' => $payload]);
        }
    }

    public function updateAddresses(Server $server, array $addressIds)
    {
        $currentAddresses = $server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter(
            $currentAddresses, fn ($id) => !in_array($id, $addressIds),
        );

        if (!empty($addressesToAdd)) {
            $this->repository->attachAddresses($server, $addressesToAdd);
        }

        if (!empty($addressesToRemove)) {
            Address::query()
                   ->where('server_id', $server->id)
                   ->whereIn('id', $addressesToRemove)
                   ->update(['server_id' => null]);
        }
    }
}
