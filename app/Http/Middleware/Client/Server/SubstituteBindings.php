<?php

namespace Convoy\Http\Middleware\Client\Server;

use Closure;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings as Middleware;

class SubstituteBindings extends Middleware
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->substituteBindings('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)->firstOrFail();
        });


        return parent::handle($request, $next);
    }
}
