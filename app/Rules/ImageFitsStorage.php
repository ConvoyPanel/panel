<?php

namespace App\Rules;

use App\Models\ImageDefinition;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

/**
 * Refuses a plan smaller than the image needs.
 *
 * An imported disk arrives at the source's virtual size and `qm disk resize`
 * only grows, so this is a hard floor rather than a recommendation -- a plan
 * under it cannot be satisfied at all, not even badly.
 *
 * It is now answered from the stored version instead of by reading the disk
 * size off the node, which is both faster and correct for a node that has never
 * seen this image.
 */
class ImageFitsStorage implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $diskLimit = Arr::get($this->data, 'limits.disk');

        if (is_null($diskLimit)) {
            return;
        }

        $version = ImageDefinition::where('uuid', $value)->first()?->latestVersion();

        if (! $version) {
            return;
        }

        if ($version->minimumDiskSize() > $diskLimit) {
            $fail('The selected image requires more storage than allocated to the server.');
        }
    }
}
