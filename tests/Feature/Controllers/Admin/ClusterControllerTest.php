<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\User;

it('lets a root admin clear a cluster identity flag', function () {
    $cluster = Cluster::factory()->create([
        'flagged_at' => now(),
        'flag_reason' => 'Reported members [a] share nothing with recorded members [b].',
    ]);
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->postJson("/api/admin/clusters/{$cluster->id}/unflag")
        ->assertNoContent();

    expect($cluster->fresh())->flagged_at->toBeNull()->flag_reason->toBeNull();
    expect(AuditLog::query()->where('event', AuditEvent::ADMIN_CLUSTER_UNFLAGGED)->exists())->toBeTrue();
});

it('refuses a non-admin', function () {
    $cluster = Cluster::factory()->create(['flagged_at' => now()]);
    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)
        ->postJson("/api/admin/clusters/{$cluster->id}/unflag")
        ->assertForbidden();

    expect($cluster->fresh()->flagged_at)->not->toBeNull();
});
