<?php

namespace App\Rules;

use App\Models\Storage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class UniqueStorageNamePerNode implements ValidationRule
{
    public function __construct(
        private readonly int $nodeId,
        private readonly ?int $ignoreStorageId = null
    ) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (Storage::where('name', $value)
            ->whereHas('nodes', function (Builder $query) {
                $query->where('nodes.id', $this->nodeId);
            })
            ->when($this->ignoreStorageId, function (Builder $query) {
                $query->where('id', '!=', $this->ignoreStorageId);
            })
            ->exists()) {
            $fail('The storage name must be unique within the node.');
        }
    }
}
