<?php

namespace App\Rules;

use App\Models\NetworkInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class NetworkInterfaceBelongsToNode implements ValidationRule
{
    public function __construct(protected ?int $nodeId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($this->nodeId)) {
            $fail('A node must be selected.');
            return;
        }

        if (!NetworkInterface::where('id', $value)->where('node_id', $this->nodeId)->exists()) {
            $fail('The selected network interface does not belong to the specified node.');
        }
    }
}
