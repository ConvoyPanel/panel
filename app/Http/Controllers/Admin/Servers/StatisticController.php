<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Client\Servers\StatisticController as ClientStatisticController;

/**
 * A server's usage timeline from Proxmox.
 *
 * The client controller, served to the admin and application APIs. It is a subclass rather than
 * a second route to the same class so that Wayfinder keeps emitting one plain route object for
 * the client screens instead of a dict keyed by URI.
 */
class StatisticController extends ClientStatisticController {}
