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
use Illuminate\Support\Arr;
use function collect;
use function is_null;

class NetworkService
{
    private const NIC_MODELS = [
        'e1000', 'e1000-82540em', 'e1000-82544gc', 'e1000-82545em',
        'e1000e', 'i82551', 'i82557b', 'i82559er',
        'ne2k_isa', 'ne2k_pci', 'pcnet', 'rtl8139',
        'virtio', 'vmxnet3',
    ];

    public function __construct(
        private AddressRepository          $repository,
        private ProxmoxFirewallRepository  $firewallRepository,
        private CloudinitService           $cloudinitService,
        private ProxmoxCloudinitRepository $cloudinitRepository,
        private ProxmoxConfigRepository    $allocationRepository,
    ) {
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

    public function clearIpsets(Server $server): void
    {
        $this->firewallRepository->setServer($server);

        $ipSets = array_column($this->firewallRepository->getIpsets(), 'name');

        foreach ($ipSets as $ipSet) {
            $this->deleteIpset($server, $ipSet);
        }
    }

    public function lockIps(Server $server, array $addresses, string $ipsetName): void
    {
        $this->firewallRepository->setServer($server);

        $this->firewallRepository->createIpset($ipsetName);

        foreach ($addresses as $address) {
            $this->firewallRepository->lockIp($ipsetName, $address);
        }
    }

    public function getMacAddresses(Server $server, bool $eloquent = true, bool $proxmox = false): MacAddressData
    {
        if ($eloquent) {
            $addresses = $this->getAddresses($server);

            $eloquentMacAddress = $addresses->ipv4->first()
                ?->mac_address ?? $addresses->ipv6->first()?->mac_address;
        }

        if ($proxmox) {
            $config = $this->cloudinitRepository->setServer($server)->getConfig();

            $proxmoxMacAddress = null;
            if (preg_match(
                "/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su",
                Arr::get($config, 'net0', ''),
                $matches,
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

    public function syncSettings(Server $server): void
    {
        $macAddresses = $this->getMacAddresses($server, true, true);
        $addresses = $this->getAddresses($server);

        $this->clearIpsets($server);
        $this->cloudinitService->updateIpConfig($server, CloudinitAddressConfigData::from([
            'ipv4' => $addresses->ipv4->first()?->toArray(),
            'ipv6' => $addresses->ipv6->first()?->toArray(),
        ]));
        $this->lockIps(
            $server,
            array_unique(Arr::flatten($server->addresses()->get(['address'])->toArray())),
            'ipfilter-net0',
        );
        $this->firewallRepository->setServer($server)->updateOptions([
            'enable' => true,
            'ipfilter' => true,
            'policy_in' => 'ACCEPT',
            'policy_out' => 'ACCEPT',
        ]);

        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;
        $this->ensureNet0BaseConfig($server, $macAddress);
    }

    public function updateRateLimit(Server $server, ?int $mebibytes = null): void
    {
        $macAddresses = $this->getMacAddresses($server, true, true);
        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;

        $rawConfig = $this->allocationRepository->setServer($server)->getConfig();
        $networkConfig = collect($rawConfig)->where('key', '=', 'net0')->first();

        if (is_null($networkConfig)) {
            return;
        }

        $parsedConfig = $this->parseConfig($networkConfig['value']);

        $this->applyBaseNet0Fields($parsedConfig, $server, $macAddress);

        if (is_null($mebibytes)) {
            $parsedConfig = array_values(
                array_filter($parsedConfig, fn ($item) => $item->key !== 'rate')
            );
        } else {
            $this->setConfigField($parsedConfig, 'rate', $mebibytes, 'rate');
        }

        $newConfig = implode(',', array_map(fn ($item) => "{$item->key}={$item->value}", $parsedConfig));

        if (
            $this->normalizeNetConfigForComparison($networkConfig['value']) ===
            $this->normalizeNetConfigForComparison($newConfig)
        ) {
            return;
        }

        $this->allocationRepository->setServer($server)->update(['net0' => $newConfig]);
    }

    public function updateAddresses(Server $server, array $addressIds): void
    {
        $currentAddresses = $server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter(
            $currentAddresses,
            fn ($id) => !in_array($id, $addressIds),
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

    private function ensureNet0BaseConfig(Server $server, string $macAddress): void
    {
        $rawConfig = $this->allocationRepository->setServer($server)->getConfig();
        $net0 = collect($rawConfig)->where('key', '=', 'net0')->first();
        $parsedConfig = $net0 ? $this->parseConfig($net0['value']) : [];

        $this->applyBaseNet0Fields($parsedConfig, $server, $macAddress);

        $newConfig = implode(',', array_map(fn ($item) => "{$item->key}={$item->value}", $parsedConfig));

        if ($net0 && $this->normalizeNetConfigForComparison($net0['value']) === $this->normalizeNetConfigForComparison($newConfig)) {
            return;
        }

        $this->allocationRepository->setServer($server)->update(['net0' => $newConfig]);
    }

    private function applyBaseNet0Fields(array &$parsedConfig, Server $server, string $macAddress): void
    {
        $this->setConfigField($parsedConfig, self::NIC_MODELS, $macAddress, 'virtio');
        $this->setConfigField($parsedConfig, 'bridge', $server->node->network, 'bridge');
        $this->setConfigField($parsedConfig, 'firewall', 1, 'firewall');
    }

    private function setConfigField(array &$parsedConfig, string|array $keys, mixed $value, string $defaultKey): void
    {
        $keys = (array) $keys;
        foreach ($parsedConfig as $item) {
            if (in_array($item->key, $keys, true)) {
                $item->value = $value;
                return;
            }
        }
        $parsedConfig[] = (object) ['key' => $defaultKey, 'value' => $value];
    }

    private function normalizeNetConfigForComparison(string $config): array
    {
        $normalized = [];
        foreach ($this->parseConfig($config) as $item) {
            $key = strtolower(trim((string) $item->key));
            $value = trim((string) $item->value);

            if (preg_match('/^(?:[0-9a-f]{2}:){5}[0-9a-f]{2}$/i', $value)) {
                $value = strtolower($value);
            }

            $normalized[$key] = $value;
        }

        ksort($normalized);

        return $normalized;
    }

    private function parseConfig(string $config): array
    {
        $parsedObjects = [];

        foreach (explode(',', $config) as $component) {
            $component = trim($component);
            if ($component === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $component, 2), 2, '');
            $parsedObjects[] = (object) ['key' => $key, 'value' => $value];
        }

        return $parsedObjects;
    }
}
