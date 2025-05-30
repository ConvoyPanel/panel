<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\NetworkDeviceModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class NetworkDeviceData extends Data
{
    public function __construct(
        public int $id,
        public NetworkDeviceModel $model,
        public ?string $macAddress,

        /**
         * @var $bridge
         *
         * Bridge to attach the network device to. The Proxmox VE standard bridge is called 'vmbr0'. If not specified, Proxmox creates a KVM user (NATed) network device.
         */
        public ?string $bridge,

        /**
         * @var $vlanTag
         *
         * VLAN tag to apply to packets on this interface (1-4094).
         */
        public ?int $vlanTag,
        public bool $isFirewallEnabled,
        public ?int $rateLimit,

        /**
         * @var $packetQueueCount
         *
         * Number of packet queues to be used on the device.
         */
        public ?int $packetQueueCount,
        public ?int $mtu,

        /**
         * @var $isLinkDown
         *
         * Whether this interface should be disconnected (like pulling the plug).
         */
        public ?bool $isLinkDown,

        /**
         * @var $vlanTrunks
         *
         * List of VLANs that are allowed to pass through this interface.
         */
        public ?string $vlanTrunks,
    ) {}

    /**
     * Creates a Collection of NetworkDeviceData instances from a raw Proxmox config array.
     *
     * @param  array  $raw  The raw configuration array from Proxmox API (e.g., the 'data' object).
     * @return Collection<int, NetworkDeviceData>
     */
    public static function fromRaw(array $raw): Collection
    {
        $networkDevices = collect();

        foreach ($raw as $key => $value) {
            if (Str::startsWith($key, 'net') && is_string($value)) {
                $id = (int) Str::replace('net', '', $key);
                $parsedConfig = self::parseNetString($value);

                if ($parsedConfig === null) {
                    // Log or handle parsing error if necessary
                    continue;
                }

                $networkDevices->push(new self(
                    id: $id,
                    model: NetworkDeviceModel::from($parsedConfig['model']),
                    macAddress: $parsedConfig['macaddr'] ?? null,
                    bridge: $parsedConfig['bridge'] ?? null,
                    vlanTag: isset($parsedConfig['tag']) ? (int) $parsedConfig['tag'] : null,
                    isFirewallEnabled: isset($parsedConfig['firewall']) ? (bool) (int) $parsedConfig['firewall'] : null, // Proxmox uses 1/0
                    rateLimit: isset($parsedConfig['rate']) ? floor($parsedConfig['rate'] * 1024 * 1024) : null,
                    packetQueueCount: isset($parsedConfig['queues']) ? (int) $parsedConfig['queues'] : null,
                    mtu: isset($parsedConfig['mtu']) ? (int) $parsedConfig['mtu'] : null,
                    isLinkDown: isset($parsedConfig['link_down']) ? (bool) (int) $parsedConfig['link_down'] : null, // Proxmox uses 1/0
                    vlanTrunks: $parsedConfig['trunks'] ?? null
                ));
            }
        }

        return $networkDevices;
    }

    /**
     * Parses the Proxmox net string (e.g., "virtio=MAC,bridge=vmbr0,tag=10")
     * into an associative array.
     *
     * @param  string  $netString  The network configuration string.
     * @return array<string, string|null>|null Parsed configuration or null on failure.
     */
    private static function parseNetString(string $netString): ?array
    {
        $config = [];
        $parts = explode(',', $netString);

        if (count($parts) === 0) {
            return null; // Invalid string
        }

        // First part is model[=macaddr]
        $modelPart = array_shift($parts);
        $modelMacSplit = explode('=', $modelPart, 2);
        $config['model'] = trim($modelMacSplit[0]);

        if (count($modelMacSplit) === 2) {
            $config['macaddr'] = trim($modelMacSplit[1]);
        } else {
            $config['macaddr'] = null; // No MAC address explicitly set with model
        }

        // Parse remaining key=value pairs
        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $config[trim($kv[0])] = trim($kv[1]);
            }
        }

        return $config;
    }

    /**
     * Converts the NetworkDeviceData instance to a Proxmox-compatible string format.
     *
     * @return array{string, string} Returns a KV pair array with the key as the device ID and the value as the configuration string.
     */
    public function toProxmoxString(): array {
        $config = [];
        
        // Start with model with optional MAC address
        if ($this->macAddress) {
            $config[] = "{$this->model->value}={$this->macAddress}";
        } else {
            $config[] = $this->model->value;
        }
        
        // Add bridge if set
        if ($this->bridge) {
            $config[] = "bridge={$this->bridge}";
        }
        
        // Add firewall setting if set
        if ($this->isFirewallEnabled !== null) {
            $config[] = "firewall=" . ($this->isFirewallEnabled ? '1' : '0');
        }
        
        // Add link_down setting if set
        if ($this->isLinkDown !== null) {
            $config[] = "link_down=" . ($this->isLinkDown ? '1' : '0');
        }
        
        // Add MTU if set
        if ($this->mtu !== null) {
            $config[] = "mtu={$this->mtu}";
        }
        
        // Add queue count if set
        if ($this->packetQueueCount !== null) {
            $config[] = "queues={$this->packetQueueCount}";
        }
        
        // Add rate limit if set
        if ($this->rateLimit !== null) {
            $convertedRate = $this->rateLimit / (1024 * 1024); // Convert from bytes to MiB
            $config[] = "rate={$convertedRate}";
        }
        
        // Add VLAN tag if set
        if ($this->vlanTag !== null) {
            $config[] = "tag={$this->vlanTag}";
        }
        
        // Add VLAN trunks if set
        if ($this->vlanTrunks !== null) {
            $config[] = "trunks={$this->vlanTrunks}";
        }
        
        // Join all parameters with commas
        $configString = implode(',', $config);
        
        // Return key-value pair for Proxmox configuration
        return ["net{$this->id}", $configString];
    }
}
