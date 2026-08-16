<?php

namespace App\Http\Requests\Client\Servers\Firewall;

class UpdateFirewallRuleRequest extends FirewallRuleRequest
{
    // Same shape as creating one. The rule's position is not editable here --
    // Proxmox ignores every other field in a request that carries `moveto`,
    // so reordering is its own endpoint.
}
