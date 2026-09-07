<?php

namespace App\Rules;

use App\Models\ImageDefinition;
use App\Models\Node;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Whether this image can actually be built on the chosen node.
 *
 * The old rule asked the node whether a VMID was sitting there as a template.
 * Nothing is pre-installed now, so both halves of the question are answerable
 * from the panel: does the image have a build to hand out, and does the node
 * have somewhere to put one. The second is the common failure -- Proxmox keeps
 * the `import` content type off by default -- and it is worth catching here,
 * where it can name the fix, rather than three jobs into a deployment.
 */
class ImageIsAvailable implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $definition = ImageDefinition::where('uuid', $value)->first();

        if (! $definition) {
            return;
        }

        if (is_null($definition->latestVersion())) {
            $fail('The selected image has no published version to install.');

            return;
        }

        $nodeId = $this->data['node_id'] ?? null;

        if (is_null($nodeId)) {
            return;
        }

        $node = Node::find($nodeId);

        if ($node && is_null($node->importStorage())) {
            $fail("No storage on {$node->name} accepts disk images. Add `Import` to a storage's content types in Proxmox.");
        }
    }
}
