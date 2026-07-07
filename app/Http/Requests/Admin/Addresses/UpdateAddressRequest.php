<?php

namespace App\Http\Requests\Admin\Addresses;

use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\Server;

class UpdateAddressRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'server_id' => [
                ...Address::getRules()['server_id'],
                function (string $attribute, mixed $value, \Closure $fail) {
                    // check that the address can be assigned to the server
                    if (! $value) {
                        return; // No server selected, so no validation needed
                    }

                    $address = $this->parameter('address', Address::class);

                    // A reserved address is fully locked — it must be unreserved before it can be
                    // assigned to a server.
                    if ($address->state === \App\Enums\Network\AddressState::Reserved) {
                        $fail('This address is reserved. Unreserve it before assigning it to a server.');

                        return;
                    }

                    $server = Server::find($value);

                    if (! $server) {
                        return; // Server doesn't exist, other validation rules will catch this
                    }

                    // Get the address block group for this address
                    $addressBlockGroup = $address->addressBlock->addressBlockGroup;

                    // Check if the server's node has any network interfaces that are associated with this address block group
                    $nodeHasCompatibleInterface = $server->node->networkInterfaces()
                        ->whereHas('addressBlockGroups', function ($query) use ($addressBlockGroup) {
                            $query->where('address_block_groups.id', $addressBlockGroup->id);
                        })
                        ->exists();

                    if (! $nodeHasCompatibleInterface) {
                        $fail("This address cannot be assigned to the server because the server's node does not have a network interface assigned to the address block group.");
                    }
                },
            ],
        ];
    }
}
