<?php

namespace App\Rules;

use App\Models\Node;
use App\Models\Server;
use App\Repositories\Proxmox\Node\ProxmoxAllocationRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VMIDIsAvailable implements ValidationRule
{
    public function __construct(protected ?int $nodeId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($this->nodeId)) {
            return;
        }

        if (Server::where('vmid', $value)->where('node_id', $this->nodeId)->exists()) {
            $fail('The specified VMID is already in use on this node.');

            return;
        }

        $node = Node::find($this->nodeId);
        if (!$node) {
            return;
        }

        $repository = app(ProxmoxAllocationRepository::class)->setNode($node);
        if (!$repository->isVMIDAvailable((int) $value)) {
            $fail('The specified VMID is not available for use on Proxmox.');
        }
    }
}
