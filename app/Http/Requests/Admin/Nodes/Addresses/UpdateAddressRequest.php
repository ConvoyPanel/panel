<?php

namespace Convoy\Http\Requests\Admin\Nodes\Addresses;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Address;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(Address::getRulesForUpdate($this->parameter('address', Address::class)), ['node_id']);
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // if the type is ipv4 make sure both the address and gateway are valid ipv4 addresses and do the same for ipv6
            if ($this->type === 'ipv4') {
                if (! filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $validator->errors()->add('address', 'The address must be a valid IPv4 address.');
                }

                if (! filter_var($this->gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $validator->errors()->add('gateway', 'The gateway must be a valid IPv4 address.');
                }
            } elseif ($this->type === 'ipv6') {
                if (! filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $validator->errors()->add('address', 'The address must be a valid IPv6 address.');
                }

                if (! filter_var($this->gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $validator->errors()->add('gateway', 'The gateway must be a valid IPv6 address.');
                }
            }

            $address = $this->parameter('address', Address::class);
            $nodeId = $this->route()->originalParameter('node');

            // check for duplicate addresses
            if ($address->address !== $this->address && Address::where([['node_id', '=', $nodeId], ['address', '=', $this->address]])->exists()) {
                $validator->errors()->add('address', 'The address has already been imported.');
            }
        });
    }
}
