<?php

namespace App\Rules;

use App\Models\ImageDefinition;
use App\Models\Node;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Whether the chosen node can run the image's provisioner scripts.
 *
 * Both halves are checked here, where the error can name the fix, rather than
 * in the deployment step after the disk has been imported: the node's Anchor
 * has to be able to write snippets, and the node needs a storage to put them
 * on.
 */
class ProvisionersCanRunOn implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // An import keeps the disk it finds and installs nothing.
        if (array_key_exists('should_create_vm', $this->data) && ! $this->data['should_create_vm']) {
            return;
        }

        $definition = ImageDefinition::where('uuid', $value)->first();

        if (! $definition || ! $definition->provisioners()->exists()) {
            return;
        }

        $node = filled($nodeId = $this->data['node_id'] ?? null) ? Node::find($nodeId) : null;

        if (! $node) {
            return;
        }

        if (! $node->canWriteSnippets()) {
            $fail("This image runs provisioner scripts, and Anchor on {$node->name} cannot write them. Update Anchor on the node.");

            return;
        }

        if ($node->snippetStorage() === null) {
            $fail("This image runs provisioner scripts, and no storage on {$node->name} holds snippets. Add `Snippets` to a storage's content types in Proxmox.");
        }
    }
}
