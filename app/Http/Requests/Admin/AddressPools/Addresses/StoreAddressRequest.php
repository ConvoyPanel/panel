<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

use Convoy\Models\Address;
use Illuminate\Support\Arr;
use Convoy\Models\AddressPool;
use Illuminate\Validation\Validator;
use Convoy\Http\Requests\FormRequest;
use Convoy\Validation\ValidateAddressType;
use Convoy\Validation\ValidateAddressUniqueness;

class StoreAddressRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = Arr::except(Address::getRules(),'address_pool_id');

        return [
            'is_bulk_action' => 'sometimes|boolean',
            ...$rules,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $pool = $this->parameter('address_pool', AddressPool::class);

        $rules = [
            new ValidateAddressType,
        ];

        if ($this->boolean('is_bulk_action')) {
            $rules[] = new ValidateAddressUniqueness($pool->id);
        }

        $validator->after($rules);
    }
}
