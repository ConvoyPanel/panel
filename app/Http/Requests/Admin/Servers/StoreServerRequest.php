<?php

namespace App\Http\Requests\Admin\Servers;

use App\Rules\HasSufficientCPU;
use App\Rules\HasSufficientMemory;
use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\Server;
use App\Rules\HasSufficientAddresses;
use App\Rules\HasSufficientDiskSpace;
use App\Rules\NetworkInterfaceBelongsToNode;
use App\Rules\TemplateIsAvailable;
use App\Rules\VMIDIsAvailable;
use App\Services\Addresses\AddressAvailabilityService;
use Illuminate\Validation\Rule;

class StoreServerRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        // TODO: remove this
        return true;
    }

    public function rules(AddressAvailabilityService $addressAvailabilityService): array
    {
        $rules = Server::getRules();

        return [
            // Basic server information
            'name'       => $rules['name'],
            'node_id'    => $rules['node_id'],
            'storage_id' => $rules['storage_id'],
            'user_id'    => $rules['user_id'],
            'vmid'       => ['nullable', 'numeric', 'min:100', 'max:999999999', new VMIDIsAvailable($this->input('node_id'))],
            'hostname'   => $rules['hostname'],

            // Resource limits
            'limits'                    => 'required|array',
            'limits.cpu'                => [...$rules['cpu'], new HasSufficientCPU()],
            'limits.memory'             => [...$rules['memory'], new HasSufficientMemory()],
            'limits.disk'               => [...$rules['disk'], new HasSufficientDiskSpace()],
            'limits.bandwidth'          => $rules['bandwidth_limit'],

            // Snapshot limits
            'limits.snapshots'          => 'required|array',
            'limits.snapshots.count'    => $rules['snapshot_count_limit'],
            'limits.snapshots.size'     => $rules['snapshot_size_limit'],

            // Backup limits
            'limits.backups'            => 'required|array',
            'limits.backups.count'      => $rules['backup_count_limit'],
            'limits.backups.size'       => $rules['backup_size_limit'],

            // IP addresses
            'limits.network_interface_id' => [
                'required',
                'integer',
                'exists:network_interfaces,id',
                new NetworkInterfaceBelongsToNode($this->input('node_id')),
                new HasSufficientAddresses($addressAvailabilityService),
            ],
            'limits.addresses_ipv4_count' => 'nullable|integer|min:0|max:100',
            'limits.addresses_ipv6_count' => 'nullable|integer|min:0|max:100',
            'limits.addresses'        => [
                'array',
                Rule::requiredIf(function () {
                    $ipv4Count = $this->input('limits.addresses_ipv4_count');
                    $ipv6Count = $this->input('limits.addresses_ipv6_count');

                    return (blank($ipv4Count) || $ipv4Count == 0) && (blank($ipv6Count) || $ipv6Count == 0);
                }),
            ],
            'limits.addresses.*'      => [
                'integer',
                function ($attribute, $value, $fail) {
                    $address = Address::with('addressBlock.addressBlockGroup.networkInterfaces')->find($value);

                    if (!$address) {
                        $fail("The address with ID {$value} could not be found.");

                        return;
                    }

                    if ($address->server_id) {
                        $fail("The address with ID {$value} is already allocated to another server.");
                    }

                    $networkInterfaceId = $this->input('limits.network_interface_id');
                    if (!$address->addressBlock->addressBlockGroup->networkInterfaces->contains('id', $networkInterfaceId)) {
                        $fail("The address with ID {$value} does not belong to the selected network interface.");
                    }
                },
            ],

            // Server creation options
            'deferred_os_selection' => 'required|boolean',
            'account_password'      => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('should_create_vm') && !$this->input('deferred_os_selection')),
                'string',
                'min:8',
                'max:191',
            ],
            'should_create_vm'  => 'required|boolean',
            'template_uuid'         => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('should_create_vm') && !$this->input('deferred_os_selection')),
                'string',
                'exists:templates,uuid',
                new TemplateIsAvailable(),
            ],
            'start_on_completion'   => 'required|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
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
            $toMerge['template_uuid'] = null;
        }

        if (!empty($toMerge)) {
            $this->merge($toMerge);
        }
    }
}
