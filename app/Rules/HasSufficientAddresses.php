<?php

namespace App\Rules;

use App\Services\Addresses\AddressAvailabilityService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class HasSufficientAddresses implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function __construct(protected AddressAvailabilityService $service)
    {
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ipv4Count = (int) ($this->data['limits']['addresses_ipv4_count'] ?? 0);
        $ipv6Count = (int) ($this->data['limits']['addresses_ipv6_count'] ?? 0);

        if ($ipv4Count === 0 && $ipv6Count === 0) {
            return;
        }

        if (!$this->service->hasSufficientAddresses($value, $ipv4Count, $ipv6Count)) {
            $fail('The selected network interface does not have enough available IP addresses.');
        }
    }
}
