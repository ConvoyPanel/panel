<?php

namespace App\Http\Middleware\Client\Server;

use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings as Middleware;
use Symfony\Component\HttpFoundation\Response;

class SubstituteBindings extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->substituteBindings('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)
                         ->firstOrFail();
        });

        return parent::handle($request, $next);
    }
}
