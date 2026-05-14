<?php

namespace Convoy\Validation;

use Convoy\Enums\Network\AddressType;
use Illuminate\Validation\Validator;
use function Convoy\Helpers\ipv6ToInteger;

class ValidateAddressRangeSize
{
    const MAX_ADDRESSES = 65536;

    public function __construct(private AddressType $addressType) {}

    public function __invoke(Validator $validator)
    {
        $data = $validator->validated();

        if (empty($data['starting_address']) || empty($data['ending_address'])) {
            return;
        }

        if ($this->addressType === AddressType::IPV4) {
            $from = ip2long($data['starting_address']);
            $to = ip2long($data['ending_address']);

            if ($from === false || $to === false) {
                return;
            }

            $count = $to - $from + 1;
        } else {
            $from = ipv6ToInteger($data['starting_address']);
            $to = ipv6ToInteger($data['ending_address']);
            $count = gmp_intval(gmp_add(gmp_sub($to, $from), 1));
        }

        if ($count < 1) {
            $validator->errors()->add('ending_address', __('validation.address_range_order'));
            return;
        }

        if ($count > self::MAX_ADDRESSES) {
            $validator->errors()->add(
                'ending_address',
                __('validation.address_range_too_large', [
                    'count'   => number_format($count),
                    'maximum' => number_format(self::MAX_ADDRESSES),
                ]),
            );
        }
    }
}
