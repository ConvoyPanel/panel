<?php

use App\Support\Admin\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Every admin route has to resolve to a permission in the catalog.
 *
 * {@see AdminPermissions::requiredFor()} answers `null` for a path it does not recognise, which
 * only a superadmin satisfies. That is the right default -- a new route is closed rather than
 * open -- but it is also silent, so a group added without being classified would quietly be
 * unreachable for every narrower role. This is what makes that a failing build instead.
 */
it('resolves every admin route to a catalog permission', function () {
    $unclassified = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => Str::startsWith($route->uri(), 'api/admin/'))
        ->reject(fn (RoutingRoute $route) => AdminPermissions::requiredFor(
            Request::create('/'.$route->uri(), collect($route->methods())->first(
                fn (string $method) => $method !== 'HEAD',
            )),
        ) !== null)
        ->map(fn (RoutingRoute $route) => $route->uri())
        ->unique()
        ->values()
        ->all();

    expect($unclassified)->toBe([]);
});

it('derives the action from the method', function () {
    $required = fn (string $method, string $uri) => AdminPermissions::requiredFor(
        Request::create("/api/admin/{$uri}", $method),
    )?->value;

    expect($required('GET', 'locations'))->toBe('locations.read')
        ->and($required('POST', 'locations'))->toBe('locations.manage')
        ->and($required('PUT', 'locations/1'))->toBe('locations.manage')
        ->and($required('DELETE', 'locations/1'))->toBe('locations.manage');
});

it('carves out the two permissions that are not their resource', function () {
    $required = fn (string $method, string $uri) => AdminPermissions::requiredFor(
        Request::create("/api/admin/{$uri}", $method),
    )?->value;

    expect($required('POST', 'servers/12/power'))->toBe('servers.power')
        ->and($required('POST', 'users/12/generate-sso-token'))->toBe('users.impersonate')
        // The neighbouring routes are unaffected.
        ->and($required('POST', 'servers/12/settings/suspend'))->toBe('servers.manage')
        ->and($required('POST', 'users/12/invite'))->toBe('users.manage');
});

it('resolves the same permission under both prefixes', function () {
    // The admin panel and the Application API share one route file; a permission that depended on
    // which prefix the request arrived under would be a hole in exactly one of them.
    expect(AdminPermissions::requiredFor(Request::create('/api/application/nodes', 'GET')))
        ->toBe(AdminPermissions::requiredFor(Request::create('/api/admin/nodes', 'GET')));
});

it('requires an unrestricted role for a path outside the catalog', function () {
    expect(AdminPermissions::requiredFor(Request::create('/api/admin/something-new', 'GET')))
        ->toBeNull();
});
