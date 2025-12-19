<?php

namespace App\Policies;

use App\Models\AddressBlockGroup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AddressBlockGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     */
    public function attachNode(User $user, AddressBlockGroup $addressBlockGroup): bool
    {
        return $user->root_admin;
    }

    public function detachNode(User $user, AddressBlockGroup $addressBlockGroup): Response
    {
        if (!$user->root_admin) {
             return $this->deny('Only root admins can detach nodes.');
        }

        $nodeId = request()->route('node');

        if (!$nodeId) {
             // Fallback if node isn't resolved yet or passed differently, but usually it's in the route.
             // If we can't find the node, we might allow (controller handles 404) or deny.
             // However, for the specific check:
             return $this->allow();
        }

        // We need to check if any server on this node is using an IP from this block group.
        // The node is bound to the route as 'node' (which is the Node model or ID).
        // Since we don't have the Node instance passed directly to the policy method signature
        // (unless we add it, but standard policy usually takes User and Resource),
        // we can fetch it or trust the controller to do the check.
        // BUT, the user asked to do this check in the policy/request.

        // Let's resolve the node from the route if possible.
        $node = request()->route('node');
        if (!($node instanceof \App\Models\Node)) {
             // If implicit binding hasn't happened yet or it's just an ID
             $node = \App\Models\Node::find($node);
        }

        if (!$node) {
            return $this->allow(); // Let controller handle 404
        }

        // Check if any server on this node has an IP address that belongs to any block in this group.
        $hasUsedIps = \App\Models\Server::where('node_id', $node->id)
            ->whereHas('addresses.addressBlock', function ($query) use ($addressBlockGroup) {
                $query->where('address_block_group_id', $addressBlockGroup->id);
            })
            ->exists();

        if ($hasUsedIps) {
            return $this->deny('Cannot detach node because some servers on this node are using IP addresses from this block group.');
        }

        return $this->allow();
    }

    public function delete(User $user, AddressBlockGroup $addressBlockGroup): Response
    {
        $isInUse = $addressBlockGroup->addressBlocks()
            ->whereHas('addresses', function ($query) {
                $query->whereNotNull('server_id');
            })
            ->exists();

        if ($isInUse) {
            return $this->deny('This address block group cannot be deleted because it contains IP addresses currently assigned to servers.');
        }

        return $this->allow();
    }
}
