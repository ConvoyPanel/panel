<?php

namespace App\Rules;

use App\Models\Node;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class HasSufficientCPU implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nodeId = $this->data['node_id'] ?? null;
        if (is_null($nodeId)) {
            return;
        }

        $node = Node::find($nodeId);
        if (!$node) {
            return;
        }

        if ($value > $node->cpu) {
            $fail('The node does not have enough CPU cores available.');
        }
    }
}