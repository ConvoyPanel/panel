<?php

namespace Convoy\Http\Middleware\Client\Server;

use Closure;
use Convoy\Models\Server;
use Illuminate\Routing\Middleware\SubstituteBindings as Middleware;

class SubstituteBindings extends Middleware
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->bind('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)->firstOrFail();
        });

        $this->router->bind('user', function ($value, $route) {
            /** @var \Pterodactyl\Models\Subuser $match */
            $match = $route->parameter('server')
                ->subusers()
                ->whereRelation('user', 'uuid', '=', $value)
                ->firstOrFail();

            return $match->user;
        });

        return parent::handle($request, $next);
    }
}
