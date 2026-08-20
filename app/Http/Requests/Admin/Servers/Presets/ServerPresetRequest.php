<?php

namespace App\Http\Requests\Admin\Servers\Presets;

use App\Enums\Node\Storage\StorageContentType;
use App\Http\Requests\BaseApiRequest;
use App\Models\ServerPreset;
use App\Rules\NetworkInterfaceBelongsToNode;
use App\Rules\StorageAllows;
use App\Rules\VlanIsDeclaredOnInterface;
use Closure;
use Illuminate\Validation\Rule;

class ServerPresetRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = $this->method() === 'PUT'
            ? ServerPreset::getRulesForUpdate($this->parameter('server_preset', ServerPreset::class))
            : ServerPreset::getRules();

        return [
            'name' => $rules['name'],
            'description' => $rules['description'],
            'settings' => $rules['settings'],

            /*
             * A preset is partial by design: every setting is `nullable`, and
             * one that is absent simply leaves the create form's own default
             * alone. What is validated here is that the values which *are*
             * saved could actually be submitted — a preset that can only fail
             * at create time is worse than no preset.
             */
            'settings.node_id' => [
                'nullable',
                'integer',
                'exists:nodes,id',
                // Storage, bridge and extra disks are all node-scoped ids, so
                // saving one without its node would produce a preset that
                // points at nothing recognisable once applied.
                Rule::requiredIf(fn () => $this->hasNodeScopedSettings()),
            ],
            'settings.storage_id' => [
                'nullable',
                'integer',
                'exists:storages,id',
                new StorageAllows(StorageContentType::KVM),
            ],

            'settings.cpu' => 'nullable|integer|min:1|max:100000',
            // Mebibytes, as typed into the form.
            'settings.memory' => 'nullable|integer|min:128|max:1048576',
            'settings.disk' => 'nullable|integer|min:1|max:10485760',
            // Mebibytes, as typed into the form; -1 is unmetered.
            'settings.bandwidth' => 'nullable|integer|min:-1',
            // MB/s, with -1 for uncapped — which is why 0 is not allowed: a
            // zero cap is a stopped NIC, not an absent one.
            'settings.speed_limit' => ['nullable', 'numeric', 'min:-1', function (string $attribute, mixed $value, Closure $fail) {
                if ($value > -1 && $value < 1) {
                    $fail('The speed limit must be at least 1 MB/s, or -1 for uncapped.');
                }
            }],
            'settings.backup_count' => 'nullable|integer|min:-1',
            'settings.backup_size' => 'nullable|integer|min:-1',

            'settings.disks' => 'nullable|array',
            'settings.disks.*.storage_id' => [
                'required',
                'integer',
                'exists:storages,id',
                new StorageAllows(StorageContentType::KVM),
            ],
            // GiB, as typed into the form.
            'settings.disks.*.size' => 'required|numeric|min:1',

            'settings.network_interface_id' => [
                'nullable',
                'integer',
                'exists:network_interfaces,id',
                new NetworkInterfaceBelongsToNode($this->integerOrNull('settings.node_id')),
            ],
            'settings.vlan_tag' => [
                'nullable',
                'integer',
                'min:1',
                'max:4094',
                new VlanIsDeclaredOnInterface($this->integerOrNull('settings.network_interface_id')),
            ],
            'settings.addresses_ipv4_count' => 'nullable|integer|min:0|max:100',
            'settings.addresses_ipv6_count' => 'nullable|integer|min:0|max:100',

            'settings.deferred_os_selection' => 'nullable|boolean',
            'settings.should_create_vm' => 'nullable|boolean',
            // No `TemplateFitsStorage` / `TemplateIsAvailable` here: both judge a
            // template against the storage and node a server is being built on,
            // and a preset is saved long before that build exists.
            'settings.template_uuid' => 'nullable|string|exists:templates,uuid',
            'settings.template_group_uuid' => 'nullable|string|exists:template_groups,uuid',
            'settings.start_on_completion' => 'nullable|boolean',
        ];
    }

    /**
     * Whether the payload carries any setting that is only meaningful on one
     * particular node.
     */
    private function hasNodeScopedSettings(): bool
    {
        return filled($this->input('settings.storage_id'))
            || filled($this->input('settings.network_interface_id'))
            || filled($this->input('settings.disks'));
    }

    private function integerOrNull(string $key): ?int
    {
        $value = $this->input($key);

        return filled($value) ? (int) $value : null;
    }

    /**
     * The validated payload with blank settings dropped rather than stored as a
     * wall of nulls: "unset" and "explicitly nothing" mean the same thing to a
     * preset, and the shorter row is the one an admin can read in the database.
     */
    public function attributesForPreset(): array
    {
        $validated = $this->validated();

        $validated['settings'] = collect($validated['settings'] ?? [])
            ->reject(fn ($value) => $value === null || $value === [])
            ->all();

        return $validated;
    }
}
