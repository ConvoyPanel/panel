<?php

namespace Convoy\Validation;

use Convoy\Models\Address;
use Illuminate\Validation\Validator;

readonly class ValidateAddressUniqueness
{
    public function __construct(private int $addressPoolId, private ?string $existingAddress = null)
    {
    }

    public function __invoke(Validator $validator): void
    {
        $data = $validator->validated();

        if (!$this->existingAddress) {
            if (Address::where([['address_pool_id', '=', $this->addressPoolId], ['address', '=', $data['address']]],
            )->exists()) {
                $validator->errors()->add('address', 'This address has already been imported.');
            }
        } else {
            if ($this->existingAddress !== $data['address'] && Address::where(
                    [['address_pool_id', '=', $this->addressPoolId], ['address', '=', $data['address']]],
                )->exists()) {
                $validator->errors()->add('address', 'This address has already been imported.');
            }
        }
    }
}