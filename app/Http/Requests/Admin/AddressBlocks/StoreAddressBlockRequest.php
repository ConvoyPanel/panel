<?php

namespace App\Http\Requests\Admin\AddressBlocks;

use App\Http\Requests\BaseApiRequest;
use App\Models\AddressBlock;
use Illuminate\Support\Arr;

class StoreAddressBlockRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Arr::except(AddressBlock::getRules(), ['address_block_group_id']);

        // Override base_ip validation to ensure it matches the version (IPv4 or IPv6)
        $rules['base_ip'] = [
            'required',
            function (string $attribute, mixed $value, \Closure $fail) {
                $version = request()->input('version');

                if ($version === 'ipv4' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $fail('The base IP must be a valid IPv4 address when version is IPv4.');
                } elseif ($version === 'ipv6' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $fail('The base IP must be a valid IPv6 address when version is IPv6.');
                } elseif (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $fail('The base IP must be a valid IP address.');
                }
            }
        ];

        // Also validate gateway if provided
        if (array_key_exists('gateway', $rules)) {
            $rules['gateway'] = [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (empty($value)) {
                        return;
                    }

                    $version = request()->input('version');

                    if ($version === 'ipv4' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $fail('The gateway must be a valid IPv4 address when version is IPv4.');
                    } elseif ($version === 'ipv6' && !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $fail('The gateway must be a valid IPv6 address when version is IPv6.');
                    } elseif (!filter_var($value, FILTER_VALIDATE_IP)) {
                        $fail('The gateway must be a valid IP address.');
                    }
                }
            ];
        }

        return $rules;
    }
}
