<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\NetworkDeviceModel;
use App\Extensions\Spatie\Data\Proxmox\Casts\PveBooleanCast;
use App\Extensions\Spatie\Data\Proxmox\Casts\RateLimitCast;
use App\Extensions\Spatie\Data\Proxmox\MapsProxmoxProperties;
use App\Extensions\Spatie\Data\Proxmox\PropertyList;
use App\Extensions\Spatie\Data\Proxmox\ProxmoxProperty;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class NetworkDeviceData extends Data
{
    use MapsProxmoxProperties;

    public function __construct(
        public int $id,
        public NetworkDeviceModel $model,
        public ?string $macAddress,

        /**
         * @var $bridge
         *
         * Bridge to attach the network device to. The Proxmox VE standard bridge is called 'vmbr0'. If not specified, Proxmox creates a KVM user (NATed) network device.
         */
        #[ProxmoxProperty('bridge')]
        public ?string $bridge,

        /**
         * @var $vlanTag
         *
         * VLAN tag to apply to packets on this interface (1-4094).
         */
        #[ProxmoxProperty('tag')]
        public ?int $vlanTag,

        #[ProxmoxProperty('firewall', PveBooleanCast::class)]
        public ?bool $isFirewallEnabled,

        #[ProxmoxProperty('rate', RateLimitCast::class)]
        public ?int $rateLimit,

        /**
         * @var $packetQueueCount
         *
         * Number of packet queues to be used on the device.
         */
        #[ProxmoxProperty('queues')]
        public ?int $packetQueueCount,

        #[ProxmoxProperty('mtu')]
        public ?int $mtu,

        /**
         * @var $isLinkDown
         *
         * Whether this interface should be disconnected (like pulling the plug).
         */
        #[ProxmoxProperty('link_down', PveBooleanCast::class)]
        public ?bool $isLinkDown,

        /**
         * @var $vlanTrunks
         *
         * List of VLANs that are allowed to pass through this interface.
         */
        #[ProxmoxProperty('trunks')]
        public ?string $vlanTrunks,

        /**
         * @var array<string, string> $extraProperties
         *
         * Sub-keys present on the Proxmox net string that we don't explicitly
         * model. Preserved verbatim so re-emitting the device never drops a
         * field PVE (or a future version) set that we don't understand.
         */
        public array $extraProperties = [],
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
            if (! Str::startsWith($key, 'net') || ! is_string($value)) {
                continue;
            }

            // The positional head is `model[=macaddr]`; the rest is a key=value tail.
            [$head, $pairs] = PropertyList::explode($value);
            [$modelValue, $macAddress] = array_pad(explode('=', $head, 2), 2, null);

            // Typed tail fields come from the attributes; anything left over is
            // kept verbatim so it survives a re-emit.
            [$mapped, $extraProperties] = self::mapProxmoxProperties($pairs);

            $networkDevices->push(new self(
                id: (int) Str::replace('net', '', $key),
                model: NetworkDeviceModel::from(trim($modelValue)),
                macAddress: $macAddress !== null ? trim($macAddress) : null,
                bridge: $mapped['bridge'] ?? null,
                vlanTag: $mapped['vlanTag'] ?? null,
                isFirewallEnabled: $mapped['isFirewallEnabled'] ?? null,
                rateLimit: $mapped['rateLimit'] ?? null,
                packetQueueCount: $mapped['packetQueueCount'] ?? null,
                mtu: $mapped['mtu'] ?? null,
                isLinkDown: $mapped['isLinkDown'] ?? null,
                vlanTrunks: $mapped['vlanTrunks'] ?? null,
                extraProperties: $extraProperties,
            ));
        }

        return $networkDevices;
    }

    /**
     * Converts the NetworkDeviceData instance to a Proxmox-compatible string format.
     *
     * @return array{string, string} Returns a KV pair array with the key as the device ID and the value as the configuration string.
     */
    public function toProxmoxString(): array
    {
        $head = $this->macAddress
            ? "{$this->model->value}={$this->macAddress}"
            : $this->model->value;

        // Modeled keys from the attributes, then any sub-keys we don't model.
        $pairs = $this->toProxmoxProperties() + $this->extraProperties;

        return ["net{$this->id}", PropertyList::implode($head, $pairs)];
    }
}
