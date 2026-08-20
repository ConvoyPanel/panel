<?php

use App\Enums\Audit\AuditEvent;
use App\Enums\Audit\AuditRetention;
use App\Enums\Audit\AuditVisibility;

/*
 * Catalog invariants. These are cheap, but the values here are written to the database and matched
 * by the frontend, so a collision or a stray rename is expensive to discover later.
 */

it('has no duplicate event values', function () {
    $values = array_map(fn (AuditEvent $event) => $event->value, AuditEvent::cases());

    expect(array_unique($values))->toHaveCount(count($values));
});

it('namespaces every event under a dotted area prefix', function () {
    // `area.thing.verb` for most, `area.verb` where there is no intermediate noun worth naming
    // (server.renamed, auth.logout). Lowercase and dash-separated throughout, because these
    // strings are a stable API: they are stored in the database and matched by the frontend.
    foreach (AuditEvent::cases() as $event) {
        expect($event->value)->toMatch('/^[a-z0-9-]+(\.[a-z0-9-]+){1,2}$/');
    }
});

it('groups every event under a known area', function () {
    foreach (AuditEvent::cases() as $event) {
        expect(explode('.', $event->value)[0])->toBeIn(['auth', 'account', 'server', 'admin']);
    }
});

it('keeps authentication and credential events forever', function () {
    expect(AuditEvent::AUTH_LOGIN_SUCCEEDED->retention())->toBe(AuditRetention::FOREVER)
        ->and(AuditEvent::AUTH_LOGIN_FAILED->retention())->toBe(AuditRetention::FOREVER)
        ->and(AuditEvent::ACCOUNT_PASSWORD_UPDATED->retention())->toBe(AuditRetention::FOREVER)
        ->and(AuditEvent::ACCOUNT_TWO_FACTOR_DISABLED->retention())->toBe(AuditRetention::FOREVER)
        ->and(AuditEvent::ADMIN_TOKEN_CREATED->retention())->toBe(AuditRetention::FOREVER);
});

it('prunes high-churn operational events', function () {
    expect(AuditEvent::SERVER_POWER_SENT->retention())->toBe(AuditRetention::STANDARD)
        ->and(AuditEvent::SERVER_CONSOLE_SESSION_CREATED->retention())->toBe(AuditRetention::STANDARD);
});

it('reports every forever-retained event through the helper the pruner uses', function () {
    $expected = array_values(array_filter(
        AuditEvent::cases(),
        fn (AuditEvent $event) => $event->retention() === AuditRetention::FOREVER,
    ));

    expect(AuditEvent::retainedForever())->toBe($expected)->not->toBeEmpty();
});

it('defaults events to client-visible and hides the SSO impersonation token', function () {
    expect(AuditEvent::SERVER_POWER_SENT->visibility())->toBe(AuditVisibility::CLIENT)
        ->and(AuditEvent::ADMIN_SERVER_SUSPENDED->visibility())->toBe(AuditVisibility::CLIENT)
        ->and(AuditEvent::ADMIN_USER_SSO_TOKEN_GENERATED->visibility())->toBe(AuditVisibility::ADMIN_ONLY);
});
