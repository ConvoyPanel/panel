<?php

use Illuminate\Support\Facades\Route;

/*
 * Nested client routes rely on Laravel's scoped route-model binding, enabled
 * for the whole /api/client group in bootstrap/app.php, to keep a child
 * resource from being addressed through a parent that doesn't own it.
 *
 * That guarantee is structural rather than defensive: BackupController::restore()
 * and ::destroy() never compare $backup->server_id to $server->id, and neither
 * does the service layer beneath them. Moving a nested route out of the group,
 * or hanging ->withoutScopedBindings() off it, silently turns a 404 into a
 * cross-tenant read, restore or delete. These tests fail when that happens.
 */

/**
 * Nested routes that opt out of scoping on purpose.
 *
 * Short, and it should stay that way. Scoping a child through its parent is
 * cheap wherever the relationship exists, so an entry here has to say why it
 * doesn't. The ISO routes qualify: the library is panel-wide, so an ISO row
 * carries no node_id or server_id and there is no relationship for scoped
 * binding to resolve through. Every ISO is offerable on every node by design,
 * and what gates those two endpoints instead is the hidden flag, checked by
 * MediaRequest and covered in SettingsControllerTest.
 *
 * Adding an entry means a child resource is once again addressable through a
 * parent that has no claim to it, so it should be a deliberate decision with
 * the reasoning written down, not a way to quiet a failing test.
 */
$deliberateOptOuts = [
    'api/client/servers/{server}/settings/hardware/isos/{iso}/mount',
    'api/client/servers/{server}/settings/hardware/isos/{iso}/unmount',
];

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
    // Client routes only: the admin and application endpoints address a backup
    // directly rather than through a server, so there is no parent to scope to.
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/client/'))
        ->filter(fn ($route) => str_contains($route->uri(), 'backups/{backup}'));

    expect($routes)->toHaveCount(2);

    $unscoped = $routes
        ->reject(fn ($route) => $route->enforcesScopedBindings())
        ->map(fn ($route) => implode('|', $route->methods()) . ' ' . $route->uri())
        ->values()
        ->all();

    expect($unscoped)->toBe([]);
});
