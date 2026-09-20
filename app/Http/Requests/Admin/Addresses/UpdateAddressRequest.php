<?php

namespace App\Http\Requests\Admin\Addresses;

use App\Enums\Network\AddressState;
use App\Http\Requests\BaseApiRequest;
use App\Models\Address;
use App\Models\Server;
use App\Services\Addresses\AddressReachabilityService;

class UpdateAddressRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $reachability = app(AddressReachabilityService::class);

        return [
            'server_id' => [
                ...Address::getRules()['server_id'],
                function (string $attribute, mixed $value, \Closure $fail) use ($reachability) {
                    // check that the address can be assigned to the server
                    if (! $value) {
                        return; // No server selected, so no validation needed
                    }

                    $address = $this->parameter('address', Address::class);

                    // A reserved address is fully locked — it must be unreserved before it can be
                    // assigned to a server.
                    if ($address->state === AddressState::Reserved) {
                        $fail('This address is reserved. Unreserve it before assigning it to a server.');

                        return;
                    }

                    $server = Server::find($value);

                    if (! $server) {
                        return; // Server doesn't exist, other validation rules will catch this
                    }

                    if (! $reachability->isReachable($address, $server->node)) {
                        $fail("This address cannot be assigned to the server because the server's node does not have a network interface assigned to the address block group.");
                    }
                },
            ],
        ];
    }
}
