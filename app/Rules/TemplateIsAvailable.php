<?php

namespace App\Rules;

use App\Models\Node;
use App\Services\Servers\ServerCreationService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TemplateIsAvailable implements DataAwareRule, ValidationRule
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
        if (! $node) {
            return;
        }

        if (! app(ServerCreationService::class)->isTemplateAvailable($node, $value)) {
            $fail('The selected template is not available on the target node.');
        }
    }
}
