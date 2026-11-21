<?php

namespace App\Http\Controllers\Admin\Servers;

use App\Http\Controllers\Client\Servers\SubuserController as ClientSubuserController;

/**
 * Who else may reach a server.
 *
 * The client controller, served to the admin and application APIs. It is a subclass rather than
 * a second route to the same class so that Wayfinder keeps emitting one plain route object for
 * the client screens instead of a dict keyed by URI.
 */
class SubuserController extends ClientSubuserController {}
