<?php

namespace App\Http\Requests\Admin\Servers;

use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\Server;
use App\Rules\HasSufficientCPU;
use App\Rules\HasSufficientDiskSpace;
use App\Rules\HasSufficientMemory;
use App\Rules\TemplateIsAvailable;
use App\Rules\VMIDIsAvailable;
use App\Services\Servers\ServerCreationService;
use Illuminate\Validation\Rule;

/**
 * @property mixed $type
 */
class StoreServerRequest extends BaseApiRequest
{
    public function rules(): array
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
            'limits.network_interface_id' => 'required|integer|exists:network_interfaces,id',
            'limits.addresses_ipv4_count' => 'nullable|integer|min:0|max:100',
            'limits.addresses_ipv6_count' => 'nullable|integer|min:0|max:100',
            'limits.addresses'        => 'required|array',
            'limits.addresses.*'      => [
                'integer',
                function ($attribute, $value, $fail) {
                    $address = Address::find($value);

                    if (!$address) {
                        $fail("The address with ID {$value} could not be found.");

                        return;
                    }

                    if ($address->server_id) {
                        $fail("The address with ID {$value} is already allocated to another server.");
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

        if ($this->filled('limits.addresses_ipv4_count') || $this->filled('limits.addresses_ipv6_count')) {
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
