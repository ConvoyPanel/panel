<?php

namespace App\Rules;

use App\Models\Node;
use App\Services\Servers\ServerCreationService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class TemplateFitsStorage implements DataAwareRule, ValidationRule
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
        $diskLimit = Arr::get($this->data, 'limits.disk');

        if (is_null($nodeId) || is_null($diskLimit)) {
            return;
        }

        $node = Node::find($nodeId);
        if (! $node) {
            return;
        }

        $template = app(ServerCreationService::class)->getTemplate($node, $value);

        if (! $template) {
            return;
        }

        if ($template->maxDiskSpace > $diskLimit) {
            $fail('The selected template requires more storage than allocated to the server.');
        }
    }
}
