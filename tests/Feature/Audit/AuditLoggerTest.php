<?php

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\SystemActor;
use App\Models\User;
use Illuminate\Support\Facades\Route;

it('records the event, actor, subject and properties', function () {
    $user = User::factory()->create();
    $subject = User::factory()->create();

    $this->actingAs($user);

    Audit::record(
        AuditEvent::ADMIN_USER_UPDATED,
        subject: $subject,
        properties: ['changed' => ['email']],
    );

    $log = AuditLog::query()->sole();

    expect($log->event)->toBe(AuditEvent::ADMIN_USER_UPDATED)
        ->and($log->actor->is($user))->toBeTrue()
        ->and($log->subject->is($subject))->toBeTrue()
        ->and($log->properties->toArray())->toBe(['changed' => ['email']])
        ->and($log->created_at)->not->toBeNull();
});

it('falls back to the authenticated user as the actor', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Audit::record(AuditEvent::ACCOUNT_PASSWORD_UPDATED, subject: $user);

    expect(AuditLog::query()->sole()->actor->is($user))->toBeTrue();
});

it('lets an explicit actor override the authenticated user', function () {
    $session = User::factory()->create();
    $explicit = User::factory()->create();
    $this->actingAs($session);

    Audit::record(AuditEvent::ADMIN_SERVER_SUSPENDED, actor: $explicit);

    expect(AuditLog::query()->sole()->actor->is($explicit))->toBeTrue();
});

it('records a null actor when nobody is authenticated', function () {
    Audit::record(AuditEvent::AUTH_LOGIN_FAILED, properties: ['email' => 'nobody@example.com']);

    $log = AuditLog::query()->sole();

    expect($log->actor)->toBeNull()
        ->and($log->actor_type)->toBeNull()
        ->and($log->actor_id)->toBeNull();
});

it('attributes a system actor, not just users', function () {
    // Panel-wide application tokens are owned by the SystemActor, and Sanctum resolves it as the
    // request's user even though it is not Authenticatable — which is why actingAs() cannot be
    // used here and why the actor morph must not be typed to User.
    Audit::record(AuditEvent::ADMIN_NODE_CREATED, actor: SystemActor::instance());

    $log = AuditLog::query()->sole();

    expect($log->actor)->toBeInstanceOf(SystemActor::class)
        ->and($log->actor_label)->toBe('System');
});

it('keeps the actor readable after the user is deleted', function () {
    $user = User::factory()->create(['name' => 'Deleted Admin']);
    $this->actingAs($user);

    Audit::record(AuditEvent::ADMIN_NODE_DELETED);

    $user->delete();

    $log = AuditLog::query()->sole();

    // Nothing in this panel soft-deletes, so the morph is gone — but the denormalised label
    // remains. Otherwise deleting an account would quietly erase the record of what it did, which
    // is the one thing an audit log must not allow.
    expect($log->actor)->toBeNull()
        ->and($log->actor_label)->toBe('Deleted Admin')
        ->and($log->actor_id)->toBe($user->id);
});

it('records no subject for panel-wide events', function () {
    Audit::record(AuditEvent::ADMIN_SETTINGS_BANDWIDTH_UPDATED);

    $log = AuditLog::query()->sole();

    expect($log->subject)->toBeNull()->and($log->subject_type)->toBeNull();
});

it('leaves ip and user agent null outside an http request', function () {
    // The container hands back a synthetic Request in console context, REMOTE_ADDR and all;
    // recording its 127.0.0.1 as though a person had connected from it would be a lie in the
    // audit trail, so anything without a resolved route records nothing.
    Audit::record(AuditEvent::ADMIN_NODE_UPDATED);

    $log = AuditLog::query()->sole();

    expect($log->ip)->toBeNull()->and($log->user_agent)->toBeNull();
});

it('captures ip and user agent from a real request', function () {
    // The other half of the pair above: prove the console guard has not simply disabled capture
    // everywhere. A throwaway route is the smallest thing that produces a resolved route.
    // Under /api on purpose: routes/base.php serves the SPA from a catch-all wildcard that
    // swallows every path not prefixed with api or authorize.
    Route::get('/api/__audit_probe', function () {
        Audit::record(AuditEvent::SERVER_POWER_SENT);

        return response()->noContent();
    });

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeader('User-Agent', 'Probe/1.0')
        ->get('/api/__audit_probe')
        ->assertSuccessful();

    $log = AuditLog::query()->sole();

    expect($log->ip)->toBe('203.0.113.9')
        ->and($log->user_agent)->toBe('Probe/1.0');
});

it('shares one batch uuid across everything recorded inside a batch', function () {
    Audit::batch(function () {
        Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED);
        Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED);
    });

    $batches = AuditLog::query()->pluck('batch');

    expect($batches)->toHaveCount(2)
        ->and($batches->unique())->toHaveCount(1)
        ->and($batches->first())->not->toBeNull();
});

it('nests batches without losing the outer uuid', function () {
    Audit::batch(function () {
        Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED);

        Audit::batch(function () {
            Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED);
        });

        Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED);
    });

    expect(AuditLog::query()->pluck('batch')->unique())->toHaveCount(1);
});

it('clears the batch uuid once the batch ends', function () {
    Audit::batch(fn () => Audit::record(AuditEvent::SERVER_FIREWALL_RULE_DELETED));

    Audit::record(AuditEvent::SERVER_POWER_SENT);

    expect(AuditLog::query()->orderBy('id')->pluck('batch')->last())->toBeNull();
});

it('releases the batch uuid even when the callback throws', function () {
    // try/finally, not a plain sequential decrement: a throwing callback used to be able to strand
    // the counter above zero and stamp every later entry in the request with a stale batch.
    try {
        Audit::batch(function () {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // expected
    }

    Audit::record(AuditEvent::SERVER_POWER_SENT);

    expect(AuditLog::query()->sole()->batch)->toBeNull();
});
