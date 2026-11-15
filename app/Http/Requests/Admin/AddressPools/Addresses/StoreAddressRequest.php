<?php

namespace Convoy\Http\Requests\Admin\AddressPools\Addresses;

use Convoy\Enums\Network\AddressType;
use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Validation\ValidateAddressType;
use Convoy\Validation\ValidateAddressRangeSize;
use Convoy\Validation\ValidateAddressUniqueness;
use Illuminate\Support\Arr;

class StoreAddressRequest extends BaseApiRequest
{
    use LowercasesAddresses;

    public function rules(): array
    {
        $rules = Arr::except(Address::getRules(), ['address_pool_id', 'address']);

        return [
            'is_bulk_action' => 'sometimes|boolean',
            'starting_address' => 'required_if:is_bulk_action,1|exclude_if:is_bulk_action,0|ip',
            'ending_address' => 'required_if:is_bulk_action,1|exclude_if:is_bulk_action,0|ip',
            'address' => 'required_if:is_bulk_action,0|exclude_if:is_bulk_action,1|ip',
            ...$rules,
        ];
    }

    public function after(): array
    {
        $rules = [];

        if ($this->boolean('is_bulk_action')) {
            $rules[] = new ValidateAddressType(
                $this->enum('type', AddressType::class),
                ['starting_address', 'ending_address', 'gateway'],
            );
            $rules[] = new ValidateAddressRangeSize(
                $this->enum('type', AddressType::class),
            );
        }

        if (!$this->boolean('is_bulk_action')) {
            $pool = $this->parameter('address_pool', AddressPool::class);
            $rules[] = new ValidateAddressType(
                $this->enum('type', AddressType::class), ['address', 'gateway'],
            );
            $rules[] = new ValidateAddressUniqueness($pool->id);
        }

        return $rules;
    }
}
