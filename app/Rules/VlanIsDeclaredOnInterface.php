<?php

namespace App\Rules;

use App\Models\NetworkInterface;
use App\Models\Vlan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A server may only be put on a VLAN that has been declared on its bridge.
 *
 * The bridge's own default tag is deliberately *not* held to this — it is a
 * plain column on `network_interfaces` and can name a tag nobody declared, which
 * is what still produces "undeclared" VLANs in the topology view.
 */
class VlanIsDeclaredOnInterface implements ValidationRule
{
    public function __construct(protected ?int $networkInterfaceId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $interface = $this->networkInterfaceId
            ? NetworkInterface::find($this->networkInterfaceId)
            : null;

        if (! $interface) {
            $fail('A network interface must be selected before assigning a VLAN.');

            return;
        }

        if (! $interface->is_vlan_aware) {
            $fail('The selected network interface must be VLAN-aware before assigning a VLAN tag.');

            return;
        }

        $declared = Vlan::query()
            ->where('network_interface_id', $interface->id)
            ->where('tag', $value)
            ->exists();

        if (! $declared) {
            $fail("VLAN {$value} has not been declared on {$interface->name}. Declare it on the node's Network page first.");
        }
    }
}
