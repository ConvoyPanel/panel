<?php

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\User;
use App\Settings\AuditSettings;

function recordFor($subject, AuditEvent $event, ?User $actor = null): AuditLog
{
    return Audit::record($event, subject: $subject, actor: $actor);
}

function revealStaffIdentity(bool $reveal): void
{
    $settings = app(AuditSettings::class);
    $settings->reveal_staff_identity = $reveal;
    $settings->save();
}

it('returns the audit feed for a server the caller owns', function () {
    [$user, , , $server] = createServerModel();
    recordFor($server, AuditEvent::SERVER_POWER_SENT, $user);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->assertJsonPath('items.0.event', 'server.power.sent')
        ->assertJsonPath('items.0.actor.label', $user->name)
        ->assertJsonPath('items.0.actor.type', 'user');
});

it('hides admin-only events from the owner', function () {
    [$user, , , $server] = createServerModel();
    recordFor($server, AuditEvent::SERVER_POWER_SENT, $user);
    recordFor($server, AuditEvent::ADMIN_USER_SSO_TOKEN_GENERATED, admin());

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.event', 'server.power.sent');
});

it('masks the admin who acted on a customer server by default', function () {
    [$user, , , $server] = createServerModel();
    $staff = admin();
    $staff->forceFill(['name' => 'Alex Staffer'])->save();

    recordFor($server, AuditEvent::ADMIN_SERVER_SUSPENDED, $staff);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        // The action is visible; the individual is not.
        ->assertJsonPath('items.0.event', 'admin.server.suspended')
        ->assertJsonPath('items.0.actor.type', 'staff')
        ->assertJsonPath('items.0.actor.label', 'Staff')
        ->assertJsonPath('items.0.actor.id', null);
});

it('names the admin once the operator turns identity reveal on', function () {
    [$user, , , $server] = createServerModel();
    $staff = admin();
    $staff->forceFill(['name' => 'Alex Staffer'])->save();

    recordFor($server, AuditEvent::ADMIN_SERVER_SUSPENDED, $staff);
    revealStaffIdentity(true);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->assertJsonPath('items.0.actor.type', 'user')
        ->assertJsonPath('items.0.actor.label', 'Alex Staffer');
});

it('never masks anything from an admin viewer', function () {
    [, , , $server] = createServerModel();
    $staff = admin();
    $staff->forceFill(['name' => 'Alex Staffer'])->save();

    recordFor($server, AuditEvent::ADMIN_SERVER_SUSPENDED, $staff);
    revealStaffIdentity(false);

    $this->actingAs(admin())
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->assertJsonPath('items.0.actor.label', 'Alex Staffer');
});

it('shows a viewer their own address but not anyone else\'s', function () {
    [$user, , , $server] = createServerModel();
    $other = User::factory()->create();

    $mine = recordFor($server, AuditEvent::SERVER_POWER_SENT, $user);
    $mine->forceFill(['ip' => '198.51.100.7'])->save();

    $theirs = recordFor($server, AuditEvent::SERVER_RENAMED, $other);
    $theirs->forceFill(['ip' => '198.51.100.8'])->save();

    $items = collect($this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->json('items'))
        ->keyBy('event');

    expect($items['server.power.sent']['ip'])->toBe('198.51.100.7')
        ->and($items['server.renamed']['ip'])->toBeNull();
});

it('keeps one server\'s feed out of another\'s', function () {
    [$user, , , $server] = createServerModel();
    [, , , $otherServer] = createServerModel();

    recordFor($server, AuditEvent::SERVER_POWER_SENT, $user);
    recordFor($otherServer, AuditEvent::SERVER_POWER_SENT, $user);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'items');
});

it('serves the panel-wide feed to admins, filterable by area', function () {
    [, , $node, $server] = createServerModel();
    $staff = admin();

    recordFor($server, AuditEvent::SERVER_POWER_SENT, $staff);
    recordFor($node, AuditEvent::ADMIN_NODE_UPDATED, $staff);

    $this->actingAs($staff)
        ->getJson('/api/admin/audit-logs')
        ->assertSuccessful()
        ->assertJsonCount(2, 'items');

    $this->actingAs($staff)
        ->getJson('/api/admin/audit-logs?filter[area]=admin.node')
        ->assertSuccessful()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.event', 'admin.node.updated')
        ->assertJsonPath('items.0.subject.type', 'node');
});

it('refuses the panel-wide feed to a non-admin', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/admin/audit-logs')
        ->assertForbidden();
});
