<?php

namespace App\Http\Requests\Admin\Servers;

use App\Enums\Node\Storage\StorageContentType;
use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\ImageDefinition;
use App\Models\Server;
use App\Models\ServerPreset;
use App\Rules\HasSufficientAddresses;
use App\Rules\HasSufficientCPU;
use App\Rules\HasSufficientDiskSpace;
use App\Rules\HasSufficientMemory;
use App\Rules\ImageFitsStorage;
use App\Rules\ImageIsAvailable;
use App\Rules\NetworkInterfaceBelongsToNode;
use App\Rules\OwnerIsNotAGuest;
use App\Rules\ProvisionersCanRunOn;
use App\Rules\SshPublicKey;
use App\Rules\StorageAllows;
use App\Rules\VlanIsDeclaredOnInterface;
use App\Rules\VMIDIsAvailable;
use App\Services\Addresses\AddressAvailabilityService;
use App\Services\Addresses\AddressReachabilityService;
use App\Support\Provisioners\ProvisionerFieldRules;
use App\Services\Servers\ServerPlacementResolver;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreServerRequest extends BaseApiRequest
{
    private const MIB = 1024 * 1024;

    private const GIB = 1024 * 1024 * 1024;

    /** Bytes per MB, decimal, the unit the preset form stores a speed limit in. */
    private const MB = 1_000_000;

    /** Set when a location was asked for and none of its nodes could take the server. */
    private bool $placementFailed = false;

    public function rules(
        AddressAvailabilityService $addressAvailabilityService,
        AddressReachabilityService $reachability,
    ): array {
        $rules = Server::getRules();

        return [
            // Basic server information
            'name' => $rules['name'],
            'external_id' => $rules['external_id'],

            // A preset fills in whatever the request leaves out. See applyPreset().
            'server_preset_uuid' => ['sometimes', 'nullable', 'string', 'exists:server_presets,uuid'],

            // Placement. Either name the node (storage and bridge are then optional), or name
            // a location and let ServerPlacementResolver pick all three.
            'location_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:locations,id',
                function (string $attribute, mixed $value, $fail) {
                    if ($this->placementFailed) {
                        $fail('No node in this location has room for this server.');
                    }
                },
            ],
            'node_id' => $this->unlessLocated($rules['node_id']),
            'storage_id' => [
                ...$this->unlessLocated($rules['storage_id']),
                new StorageAllows(StorageContentType::KVM),
            ],
            'user_id' => [...$rules['user_id'], new OwnerIsNotAGuest],
            'vmid' => ['nullable', 'numeric', 'min:100', 'max:999999999', new VMIDIsAvailable($this->input('node_id'))],
            'hostname' => $rules['hostname'],

            // Resource limits
            'limits' => 'required|array',
            'limits.cpu' => [...$rules['cpu'], new HasSufficientCPU],
            'limits.memory' => [...$rules['memory'], new HasSufficientMemory],
            'limits.disk' => [...$rules['disk'], new HasSufficientDiskSpace],
            'limits.bandwidth' => $rules['bandwidth_limit'],
            // Persistent NIC speed cap in bytes/s (null = unlimited).
            'limits.speed_limit' => $rules['speed_limit'],

            // Optional secondary/data disks, each on its own storage. The
            // primary/OS disk stays `storage_id` + `limits.disk`; these are
            // allocated post-clone (see AllocationService::syncDisks). Capacity
            // is checked in aggregate by the HasSufficientDiskSpace rule above.
            'limits.disks' => 'sometimes|array',
            'limits.disks.*.storage_id' => ['required', 'integer', 'exists:storages,id', new StorageAllows(StorageContentType::KVM)],
            'limits.disks.*.size' => ['required', 'numeric', 'min:1'],

            // Backup limits
            'limits.backups' => 'required|array',
            'limits.backups.count' => $rules['backup_count_limit'],
            'limits.backups.size' => $rules['backup_size_limit'],

            // IP addresses
            'limits.network_interface_id' => [
                'required_without:location_id',
                'integer',
                'exists:network_interfaces,id',
                new NetworkInterfaceBelongsToNode($this->input('node_id')),
                new HasSufficientAddresses($addressAvailabilityService),
            ],
            'limits.vlan_tag' => [
                'nullable',
                'integer',
                'min:1',
                'max:4094',
                new VlanIsDeclaredOnInterface($this->input('limits.network_interface_id')),
            ],
            'limits.addresses_ipv4_count' => 'nullable|integer|min:0|max:100',
            'limits.addresses_ipv6_count' => 'nullable|integer|min:0|max:100',
            // Explicit address ids are optional. With no ids and both counts
            // at zero, ServerCreationService deliberately creates an
            // addressless server; positive counts use automatic allocation.
            'limits.addresses' => 'sometimes|array',
            'limits.addresses.*' => [
                'integer',
                function ($attribute, $value, $fail) use ($reachability) {
                    $address = Address::with('addressBlock')->find($value);

                    if (! $address) {
                        $fail("The address with ID {$value} could not be found.");

                        return;
                    }

                    if ($address->server_id) {
                        $fail("The address with ID {$value} is already allocated to another server.");
                    }

                    $networkInterfaceId = (int) $this->input('limits.network_interface_id');

                    if (! $reachability->isReachableVia($address, $networkInterfaceId)) {
                        $fail("The address with ID {$value} does not belong to the selected network interface.");
                    }
                },
            ],

            // Server creation options
            'deferred_os_selection' => 'required|boolean',
            // Either a password or SSH keys gets an installed server a way in.
            'account_password' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('should_create_vm')
                    && ! $this->input('deferred_os_selection')
                    && empty($this->input('ssh_keys'))),
                'string',
                'min:8',
                'max:191',
            ],
            'should_create_vm' => 'required|boolean',
            'image_uuid' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('should_create_vm') && ! $this->input('deferred_os_selection')),
                'string',
                'exists:image_definitions,uuid',
                new ImageIsAvailable,
                new ImageFitsStorage,
                new ProvisionersCanRunOn,
            ],
            ...ProvisionerFieldRules::for($this->image()),
            'start_on_completion' => 'required|boolean',

            // Public keys for the guest's default user, written through cloud-init with the
            // install. One key per entry.
            'ssh_keys' => ['sometimes', 'nullable', 'array', 'max:50'],
            'ssh_keys.*' => ['required', 'string', 'max:16384', new SshPublicKey],
        ];
    }

    public function attributes(): array
    {
        return ProvisionerFieldRules::attributes($this->image());
    }

    public function messages(): array
    {
        return [
            'provisioner_fields.*.regex' => 'The :attribute field cannot contain line breaks.',
        ];
    }

    private ?ImageDefinition $image = null;

    /**
     * The image the server is built from, when the build installs one. An
     * import keeps the disk it finds, so there is no first boot to run
     * provisioner scripts on and no fields to ask for.
     */
    private function image(): ?ImageDefinition
    {
        $uuid = $this->input('image_uuid');

        if (! $this->boolean('should_create_vm') || $this->boolean('deferred_os_selection') || ! is_string($uuid) || blank($uuid)) {
            return null;
        }

        return $this->image ??= ImageDefinition::with('provisioners')->where('uuid', $uuid)->first();
    }

    /**
     * A rule that is required unless a location is doing the picking, in which case it is
     * filled in by prepareForValidation() or reported against `location_id`.
     *
     * @param  list<mixed>  $rule
     * @return list<mixed>
     */
    private function unlessLocated(array $rule): array
    {
        return array_map(fn ($part) => $part === 'required' ? 'required_without:location_id' : $part, $rule);
    }

    protected function prepareForValidation(): void
    {
        $this->applyPreset();
        $this->resolvePlacement();

        $toMerge = [];

        if ($this->input('limits.addresses_ipv4_count', 0) > 0 || $this->input('limits.addresses_ipv6_count', 0) > 0) {
            $limits = $this->input('limits', []);
            $limits['addresses'] = [];
            $toMerge['limits'] = $limits;
        }

        if ($this->boolean('deferred_os_selection')) {
            $toMerge['should_create_vm'] = false;
            $toMerge['start_on_completion'] = false;
            $toMerge['account_password'] = null;
            $toMerge['image_uuid'] = null;
            $toMerge['provisioner_fields'] = null;
        }

        if (! empty($toMerge)) {
            $this->merge($toMerge);
        }
    }

    /**
     * Fill in what the request left out from a saved preset.
     *
     * A preset is the admin create form's saved answers, so it is stored in that form's units
     * (MiB for memory, disk, bandwidth and backups, GiB for extra disks, MB/s for the speed
     * cap). They are converted to the bytes this endpoint takes here. Anything the request does
     * send wins, so a preset can be applied and then adjusted.
     */
    private function applyPreset(): void
    {
        $uuid = $this->input('server_preset_uuid');
        $preset = is_string($uuid) ? ServerPreset::query()->where('uuid', $uuid)->first() : null;

        if ($preset === null) {
            return;
        }

        $settings = $preset->settings ?? [];
        $input = $this->all();
        $bytes = fn (mixed $mib) => match (true) {
            ! is_numeric($mib) => null,
            (int) $mib === -1 => -1,
            default => (int) $mib * self::MIB,
        };

        $fill = [
            'node_id' => $settings['node_id'] ?? null,
            'storage_id' => $settings['storage_id'] ?? null,
            'limits.cpu' => $settings['cpu'] ?? null,
            'limits.memory' => $bytes($settings['memory'] ?? null),
            'limits.disk' => $bytes($settings['disk'] ?? null),
            'limits.bandwidth' => $bytes($settings['bandwidth'] ?? null),
            'limits.backups.count' => $settings['backup_count'] ?? null,
            'limits.backups.size' => $bytes($settings['backup_size'] ?? null),
            'limits.network_interface_id' => $settings['network_interface_id'] ?? null,
            'limits.vlan_tag' => $settings['vlan_tag'] ?? null,
            'limits.addresses_ipv4_count' => $settings['addresses_ipv4_count'] ?? null,
            'limits.addresses_ipv6_count' => $settings['addresses_ipv6_count'] ?? null,
            'deferred_os_selection' => $settings['deferred_os_selection'] ?? null,
            'should_create_vm' => $settings['should_create_vm'] ?? null,
            'image_uuid' => $settings['image_uuid'] ?? null,
            'start_on_completion' => $settings['start_on_completion'] ?? null,
        ];

        if (isset($settings['speed_limit'])) {
            $fill['limits.speed_limit'] = (float) $settings['speed_limit'] === -1.0
                ? null
                : (int) round($settings['speed_limit'] * self::MB);
        }

        if (! empty($settings['disks'])) {
            $fill['limits.disks'] = array_map(fn (array $disk) => [
                'storage_id' => $disk['storage_id'],
                'size' => (int) round($disk['size'] * self::GIB),
            ], $settings['disks']);
        }

        // A node chosen in the request makes the preset's storage and bridge meaningless:
        // they are ids on the preset's node.
        if (Arr::has($input, 'node_id') && Arr::get($input, 'node_id') !== ($settings['node_id'] ?? null)) {
            unset($fill['storage_id'], $fill['limits.network_interface_id'], $fill['limits.vlan_tag'], $fill['limits.disks']);
        }

        // Likewise a location: the preset's node may not be in it.
        if (filled(Arr::get($input, 'location_id')) && ! Arr::has($input, 'node_id')) {
            unset($fill['node_id'], $fill['storage_id'], $fill['limits.network_interface_id'], $fill['limits.vlan_tag'], $fill['limits.disks']);
        }

        foreach ($fill as $key => $value) {
            if ($value !== null && ! Arr::has($input, $key)) {
                Arr::set($input, $key, $value);
            }
        }

        $this->replace($input);
    }

    /**
     * Pick a node, storage and bridge for whatever the request did not name.
     *
     * Runs before validation so the picked ids are checked by the same rules as hand-picked
     * ones. Inputs that are not even numbers are left for those rules to reject.
     */
    private function resolvePlacement(): void
    {
        $input = $this->all();
        $locationId = Arr::get($input, 'location_id');
        $nodeId = Arr::get($input, 'node_id');
        $storageId = Arr::get($input, 'storage_id');
        $interfaceId = Arr::get($input, 'limits.network_interface_id');

        $wantsNode = blank($nodeId) && is_numeric($locationId);
        $wantsParts = is_numeric($nodeId) && (blank($storageId) || blank($interfaceId));

        // Without a disk size there is nothing to size a storage against; the request fails
        // validation either way, so the rules report what is missing.
        if ((! $wantsNode && ! $wantsParts) || ! is_numeric(Arr::get($input, 'limits.disk'))) {
            return;
        }

        $int = fn (mixed $value) => is_numeric($value) ? (int) $value : null;

        $placement = app(ServerPlacementResolver::class)->resolve(
            locationId: $int($locationId),
            nodeId: $int($nodeId),
            storageId: $int($storageId),
            networkInterfaceId: $int($interfaceId),
            cpu: (int) $int(Arr::get($input, 'limits.cpu')),
            memory: (int) $int(Arr::get($input, 'limits.memory')),
            disk: (int) $int(Arr::get($input, 'limits.disk')),
            ipv4Count: (int) $int(Arr::get($input, 'limits.addresses_ipv4_count')),
            ipv6Count: (int) $int(Arr::get($input, 'limits.addresses_ipv6_count')),
        );

        if ($placement === null) {
            $this->placementFailed = $wantsNode;

            return;
        }

        Arr::set($input, 'node_id', $placement['node_id']);
        Arr::set($input, 'storage_id', $placement['storage_id']);
        Arr::set($input, 'limits.network_interface_id', $placement['network_interface_id']);

        $this->replace($input);
    }
}
