<?php

use Illuminate\Support\Facades\Route;

/*
 * Nested client routes rely on Laravel's scoped route-model binding, enabled
 * for the whole /api/client group in RouteServiceProvider, to keep a child
 * resource from being addressed through a parent that doesn't own it.
 *
 * That guarantee is structural rather than defensive: BackupController::restore()
 * and ::destroy() never compare $backup->server_id to $server->id, nor do
 * SettingsController::mountMedia() and ::unmountMedia() compare $iso->node_id
 * to $server->node_id, and neither does the service layer beneath any of them.
 * Moving a nested route out of the group, or hanging ->withoutScopedBindings()
 * off it, silently turns a 404 into a cross-tenant read, mount or delete.
 * These tests fail when that happens.
 */

/**
 * Nested routes that opt out of scoping on purpose.
 *
 * Empty, and it should stay that way. Scoping a child through its parent is
 * cheap when the relationship exists and can be made to exist when it doesn't:
 * ISOs hang off Node rather than Server, and Server::isos() bridges that on
 * node_id precisely so {iso} still has something to scope through.
 *
 * Adding an entry here means a child resource is once again addressable
 * through a parent that has no claim to it, so it should be a deliberate
 * decision with the reasoning written down, not a way to quiet a failing test.
 */
$deliberateOptOuts = [];

it('scopes every nested client route to its parent', function () use ($deliberateOptOuts) {
    $nested = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/client/'))
        ->filter(fn ($route) => count($route->parameterNames()) > 1);

    // Guards against the filters silently matching nothing and passing vacuously.
    expect($nested)->not->toBeEmpty();

    $unscoped = $nested
        ->reject(fn ($route) => $route->enforcesScopedBindings())
        ->map(fn ($route) => $route->uri())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($unscoped)->toBe(collect($deliberateOptOuts)->sort()->values()->all());
});

it('resolves a backup through its server', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'backups/{backup}'));

    expect($routes)->toHaveCount(2);

    $unscoped = $routes
        ->reject(fn ($route) => $route->enforcesScopedBindings())
        ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
        ->values()
        ->all();

    expect($unscoped)->toBe([]);
});
