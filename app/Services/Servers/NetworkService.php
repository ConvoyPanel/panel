<?php

namespace App\Services\Servers;

use App\Data\Server\Deployments\CloudinitAddressConfigData;
use App\Data\Server\Eloquent\ServerAddressesData;
use App\Data\Server\MacAddressData;
use App\Enums\Network\AddressVersion;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Address;
use App\Models\Server;
use App\Repositories\Eloquent\AddressRepository;
use App\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use App\Repositories\Proxmox\Server\ProxmoxFirewallRepository;
use Illuminate\Support\Arr;

use function collect;
use function is_null;

class NetworkService
{
    public function __construct(
        private AddressRepository $repository,
        private ProxmoxFirewallRepository $firewallRepository,
        private ServerFirewallService $firewallService,
        private CloudinitService $cloudinitService,
        private ProxmoxCloudinitRepository $cloudinitRepository,
        private ProxmoxConfigRepository $allocationRepository,
    ) {}

    /**
     * @throws RequestException
     */
    public function syncSettings(Server $server): void
    {
        $this->firewallService->clearIpsets($server);
        // TODO: update cloudinit config
        //        $this->cloudinitService->updateIpConfig($server, CloudinitAddressConfigData::from([
        //            'ipv4' => $addresses->ipv4->first()?->toArray(),
        //            'ipv6' => $addresses->ipv6->first()?->toArray(),
        //        ]));
        $this->firewallService->lockIps(
            $server,
            array_unique(Arr::flatten($server->addresses()->get(['address'])->toArray())),
            'ipfilter-net0',
        );

        $this->firewallService->configureFirewall($server);

        // TODO: update NIC config sync
        //        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;
        //
        //        $this->allocationRepository->setServer($server)->update(
        //            ['net0' => "virtio={$macAddress},bridge={$server->node->network},firewall=1"],
        //        );
    }

    public function setRateLimit(Server $server, ?int $mebibytes = null): void
    {
        // TODO: rewrite updateRateLimit

//        $macAddresses = $this->getMacAddresses($server, true, true);
//        $macAddress = $macAddresses->eloquent ?? $macAddresses->proxmox;
//        $rawConfig = $this->allocationRepository->setServer($server)->getConfig();
//        $networkConfig = collect($rawConfig)->where('key', '=', 'net0')->first();
//
//        if (is_null($networkConfig)) {
//            return;
//        }
//
//        $parsedConfig = $this->parseConfig($networkConfig['value']);
//
//        // List of possible models
//        $models = ['e1000', 'e1000-82540em', 'e1000-82544gc', 'e1000-82545em', 'e1000e', 'i82551', 'i82557b', 'i82559er', 'ne2k_isa', 'ne2k_pci', 'pcnet', 'rtl8139', 'virtio', 'vmxnet3'];
//
//        // Update the model with the new MAC address
//        $modelFound = false;
//        foreach ($parsedConfig as $item) {
//            if (in_array($item->key, $models)) {
//                $item->value = $macAddress;
//                $modelFound = true;
//                break;
//            }
//        }
//
//        // If no model key exists, add the default model with the MAC address
//        if (! $modelFound) {
//            $parsedConfig[] = (object) ['key' => 'virtio', 'value' => $macAddress];
//        }
//
//        // Update or create the bridge value
//        $bridgeFound = false;
//        foreach ($parsedConfig as $item) {
//            if ($item->key === 'bridge') {
//                $item->value = $server->node->network;
//                $bridgeFound = true;
//                break;
//            }
//        }
//
//        if (! $bridgeFound) {
//            $parsedConfig[] = (object) ['key' => 'bridge', 'value' => $server->node->network];
//        }
//
//        // Update or create the firewall key
//        $firewallFound = false;
//        foreach ($parsedConfig as $item) {
//            if ($item->key === 'firewall') {
//                $item->value = 1;
//                $firewallFound = true;
//                break;
//            }
//        }
//
//        if (! $firewallFound) {
//            $parsedConfig[] = (object) ['key' => 'firewall', 'value' => 1];
//        }
//
//        // Handle the rate limit
//        if (is_null($mebibytes)) {
//            // Remove the 'rate' key if $mebibytes is null
//            $parsedConfig = array_filter($parsedConfig, fn ($item) => $item->key !== 'rate');
//        } else {
//            // Add or update the 'rate' key
//            $rateUpdated = false;
//            foreach ($parsedConfig as $item) {
//                if ($item->key === 'rate') {
//                    $item->value = $mebibytes;
//                    $rateUpdated = true;
//                    break;
//                }
//            }
//
//            if (! $rateUpdated) {
//                $parsedConfig[] = (object) ['key' => 'rate', 'value' => $mebibytes];
//            }
//        }
//
//        // Rebuild the configuration string
//        $newConfig = implode(
//            ',', array_map(fn ($item) => "{$item->key}={$item->value}", $parsedConfig),
//        );
//
//        // Update the Proxmox configuration
//        $this->allocationRepository->setServer($server)->update(['net0' => $newConfig]);
    }

    public function updateAddresses(Server $server, array $addressIds): void
    {
        $currentAddresses = $server->addresses()->get()->pluck('id')->toArray();

        $addressesToAdd = array_diff($addressIds, $currentAddresses);
        $addressesToRemove = array_filter(
            $currentAddresses,
            fn ($id) => ! in_array($id, $addressIds),
        );

        if (! empty($addressesToAdd)) {
            $this->repository->attachAddresses($server, $addressesToAdd);
        }

        if (! empty($addressesToRemove)) {
            Address::query()
                ->where('server_id', $server->id)
                ->whereIn('id', $addressesToRemove)
                ->update(['server_id' => null]);
        }
    }
}
