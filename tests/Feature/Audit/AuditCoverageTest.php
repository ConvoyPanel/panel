<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Every mutating API route must reach a controller that records an audit event, or be listed as a
 * deliberate exemption.
 *
 * Be clear about what this is: a **heuristic guard**, not a proof. It checks that the controller
 * file behind a route mentions the catalog at all — not that the right event fires on the right
 * branch, and not that a service called further down does the recording. It exists so that adding a
 * mutating endpoint and forgetting to audit it fails CI rather than shipping silently, and so that
 * "we decided not to log this" is a deliberate, reviewable line in the array below.
 *
 * @see docs/audit-log-plan.md
 */

/**
 * Routes that deliberately record nothing, each with the reason it is safe to skip.
 *
 * Keyed by "CONTROLLER@method" so an exemption survives a route being renamed or remounted, and so
 * the same controller serving both /api/admin and /api/application is exempted once.
 */
const AUDIT_EXEMPT = [
    // Read-only probes despite being POST: they take a payload but change nothing.
    'NodeConnectionTestController@__invoke' => 'Connection probe; reads only.',
    'VersionController@check' => 'Checks for panel updates; changes nothing.',

    // Authentication is audited from Laravel's events, not from Fortify's controllers.
    // See App\Listeners\AuditAuthenticationSubscriber.
    'AuthenticatedSessionController@store' => 'Audited via the Login event.',
    'AuthenticatedSessionController@destroy' => 'Audited via the Logout event.',
    'TwoFactorAuthenticatedSessionController@store' => 'Audited via the Login event.',
    'PasskeyLoginController@store' => 'Audited via the Login event.',
    'SecondFactorChallengeController@store' => 'Audited via the Login event.',
    'TwoFactorAuthenticationController@store' => 'Audited via Fortify\'s two-factor events.',
    'TwoFactorAuthenticationController@destroy' => 'Audited via Fortify\'s two-factor events.',
    'ConfirmedTwoFactorAuthenticationController@store' => 'Audited via Fortify\'s two-factor events.',
    'ConfirmableIdentityController@store' => 'Re-authentication step; the action it gates is audited.',
    'RecoveryCodeController@store' => 'Audited in App\\Http\\Controllers\\Client\\RecoveryCodeController.',
];

/** The prefixes this guard governs. Horizon and the SPA shell are not ours to audit. */
const AUDITED_PREFIXES = ['api/client', 'api/admin', 'api/application', 'api/auth'];

function mutatingAuditedRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes() as $route) {
        $methods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

        if ($methods === [] || ! Str::startsWith($route->uri(), AUDITED_PREFIXES)) {
            continue;
        }

        $action = $route->getActionName();

        if ($action === 'Closure') {
            continue;
        }

        [$class, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');

        $routes[$class.'@'.$method] ??= [
            'class' => $class,
            'method' => $method,
            'uri' => $route->uri(),
        ];
    }

    return $routes;
}

it('audits every mutating api route, or exempts it on purpose', function () {
    $unaudited = [];

    // A guard on the guard: if route discovery ever returns nothing, every assertion below passes
    // vacuously and the check quietly stops protecting anything. A floor rather than an exact
    // count, so adding an endpoint does not fail this for the wrong reason.
    expect(count(mutatingAuditedRoutes()))->toBeGreaterThan(100);

    foreach (mutatingAuditedRoutes() as $handler => $route) {
        if (array_key_exists(class_basename($route['class']).'@'.$route['method'], AUDIT_EXEMPT)) {
            continue;
        }

        $file = (new ReflectionClass($route['class']))->getFileName();

        // A vendor controller cannot be given a call site; it must be exempted with a reason.
        if ($file === false || Str::contains($file, '/vendor/')) {
            $unaudited[] = "{$route['uri']} -> {$handler} (vendor controller, needs an exemption)";

            continue;
        }

        if (! Str::contains(file_get_contents($file), 'AuditEvent::')) {
            $unaudited[] = "{$route['uri']} -> {$handler}";
        }
    }

    expect($unaudited)->toBe([], 'These mutating routes record no audit event. Add a '
        ."Audit::record(...) call, or add the handler to AUDIT_EXEMPT with a reason:\n  "
        .implode("\n  ", $unaudited));
});

it('keeps the exemption list free of entries nothing uses', function () {
    // A stale exemption is a hole nobody is watching: the route it excused is gone, but the entry
    // would silently excuse any future controller that happens to reuse the name.
    $handlers = array_map(
        fn (array $route) => class_basename($route['class']).'@'.$route['method'],
        mutatingAuditedRoutes(),
    );

    expect(array_diff(array_keys(AUDIT_EXEMPT), array_values($handlers)))->toBe([]);
});
