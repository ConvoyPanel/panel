<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\CloudinitType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use function collect;
use function preg_match;
use function rawurldecode;

class CloudinitConfigData extends Data
{
    public function __construct(
        /**
         * @var $type
         *
         * Specifies the format of the cloud-init configuration. `nocloud` for Linux, `configdrive2` for Windows. Defaults based on `ostype`.
         */
        public ?CloudinitType $type,
        /**
         * @var $username
         *
         * If the VM image has a default user, this lets you
         * specify a different username to apply SSH keys and password changes to.
         */
        public ?string $username,
        public ?string $password,
        /**
         * @var $custom
         *
         * This setting lets you provide your own custom cloud-init configuration files to replace
         * automatically generated ones.
         */
        public ?string $custom,
        /**
         * @var $isAutoUpgradeEnabled
         *
         * Tells the VM to automatically upgrade its software packages after the first boot.
         */
        public bool $isAutoUpgradeEnabled,

        /**
         * @var $searchDomain
         *
         * For cloud-init, sets DNS search domain suffixes for the VM (e.g., "example.com, internal.example.com").
         * When resolving a short hostname (like "fileserver"), the OS will try appending these suffixes.
         */
        public ?string $searchDomain,

        public ?string $sshKeys,

        /**
         * @var Collection<int, IpConfigData> $ipConfigs
         *
         * Per-NIC cloud-init IP config, keyed by NIC index (`ipconfig{n}`).
         */
        public Collection $ipConfigs,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $get = fn (string $key, $default = null) => Arr::get($raw, $key, $default);
        $exists = fn (string $key) => Arr::exists($raw, $key);

        return new self(
            type: $exists('citype') ? CloudinitType::from($get('citype')) : null,
            username: $get('ciuser'),
            password: $get('cipassword'),
            custom: $get('cicustom'),
            isAutoUpgradeEnabled: $get('ciupgrade', false),
            searchDomain: $get('searchdomain'),
            sshKeys: $exists('sshkeys') ? rawurldecode($get('sshkeys')) : null,
            ipConfigs: collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^ipconfig\d+$/', $key))
                ->mapWithKeys(fn ($value, $key) => [
                    (int) substr($key, strlen('ipconfig')) => IpConfigData::fromString($value),
                ]),
        );
    }
}
