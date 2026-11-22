<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Client\Servers\FirewallController as ClientFirewallController;

/**
 * A server's firewall options and rules, read and written through to Proxmox.
 *
 * The client controller, served to the admin and application APIs. It is a subclass rather than
 * a second route to the same class so that Wayfinder keeps emitting one plain route object for
 * the client screens instead of a dict keyed by URI.
 */
class FirewallController extends ClientFirewallController {}
