<?php

namespace App\Http\Requests\Admin\AddressBlocks;

use IPLib\Factory as IPFactory;
use App\Enums\Network\AddressVersion;
use App\Http\Requests\BaseApiRequest;
use App\Models\AddressBlock;
use Illuminate\Support\Arr;

class UpdateAddressBlockRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $baseIp = $this->string('base_ip')->toString();
        /** @var ?string $gateway */
        $gateway = $this->input('gateway');

        $this->merge([
            'ip' => IPFactory::parseAddressString($baseIp)->toString(),
            'gateway' => $gateway ? IPFactory::parseAddressString($gateway)->toString() : null,
        ]);
    }

    public function rules(): array
    {
        $rules = Arr::except(AddressBlock::getRules(), ['address_block_group_id', 'version']);

        // Get the address block from the route
        /** @var AddressBlock $addressBlock */
        $addressBlock = $this->parameter('address_block', AddressBlock::class);

        // Check if any addresses are attached to servers
        $hasAttachedAddresses = $addressBlock->addresses()->whereNotNull('server_id')->exists();

        // Add validation for critical fields that can't be changed if IPs are attached to servers
        $criticalFieldValidation = function (string $attribute, mixed $value, \Closure $fail) use ($addressBlock, $hasAttachedAddresses) {
            // If the value is changing and there are attached addresses, fail validation
            if ($value != $addressBlock->{$attribute} && $hasAttachedAddresses) {
                $fail("The {$attribute} cannot be changed because some IP addresses are attached to servers.");
            }
        };

        // Override base_ip validation to ensure it matches the version (IPv4 or IPv6)
        // and can't be changed if IPs are attached to servers
        $rules['base_ip'] = [
            'required',
            function (string $attribute, mixed $value, \Closure $fail) use ($addressBlock) {
                $version = $addressBlock->version;

                if ($version === AddressVersion::IPv4 && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $fail('The base IP must be a valid IPv4 address when version is IPv4.');
                } elseif ($version === AddressVersion::IPv6 && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $fail('The base IP must be a valid IPv6 address when version is IPv6.');
                } elseif (! filter_var($value, FILTER_VALIDATE_IP)) {
                    $fail('The base IP must be a valid IP address.');
                }
            },
            $criticalFieldValidation,
        ];

        // Add validation for prefix_length_from and prefix_length_to
        $rules['prefix_length_from'][] = $criticalFieldValidation;
        $rules['prefix_length_to'][] = $criticalFieldValidation;

        $rules['gateway'] = [
            'nullable',
            function (string $attribute, mixed $value, \Closure $fail) use ($addressBlock) {
                if (empty($value)) {
                    return;
                }

                $version = $addressBlock->version;

                if ($version === AddressVersion::IPv4 && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $fail('The gateway must be a valid IPv4 address when version is IPv4.');
                } elseif ($version === AddressVersion::IPv6 && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $fail('The gateway must be a valid IPv6 address when version is IPv6.');
                } elseif (! filter_var($value, FILTER_VALIDATE_IP)) {
                    $fail('The gateway must be a valid IP address.');
                }
            },
        ];

        return $rules;
    }
}
