<?php

namespace Convoy\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        // The container healthcheck. Maintenance is the operator's choice, not a
        // fault, and with the cache driver the check itself needs Redis: with
        // Redis down it threw before /up could say which dependency failed.
        'up',
    ];
}
