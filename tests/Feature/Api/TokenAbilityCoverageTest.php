<?php

use App\Http\Middleware\DenyApiTokenAccess;
use App\Support\Api\TokenAbilities;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The ability vocabulary has to cover every resource the API actually serves.
 *
 * {@see App\Support\Api\ScopedTokenAbilities::requiredFor()} demands `*` for a path segment it
 * does not recognise, so a resource missing from the vocabulary is not merely unscopable — it is
 * unreachable by every token except a wildcard one, and silently so. This is the guard that
 * catches a route group added without a matching resource.
 */

/** @return list<string> */
function applicationRouteResources(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => Str::startsWith($route->uri(), 'api/application/'))
        // Session-only groups are unreachable by any token, so they need no ability.
        ->reject(fn (RoutingRoute $route) => in_array(
            DenyApiTokenAccess::class,
            $route->gatherMiddleware(),
            true,
        ))
        ->map(fn (RoutingRoute $route) => Str::of($route->uri())
            ->after('api/application/')
            ->before('/')
            ->toString())
        ->unique()
        ->values()
        ->all();
}

it('has an ability resource for every application API route', function () {
    expect(applicationRouteResources())->each->toBeIn(TokenAbilities::RESOURCES);
});

it('has no ability resource without a route to serve it', function () {
    expect(TokenAbilities::RESOURCES)->each->toBeIn(applicationRouteResources());
});

it('no longer offers the retired template-groups resource', function () {
    expect(TokenAbilities::all())->not->toContain('template-groups:read');
});
