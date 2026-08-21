<?php

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\ConsoleType;
use App\Models\Anchor;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Anchor\AnchorSessionService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

it('lets a machine in and records how the panel reaches it', function () {
    $anchor = Anchor::factory()->pending()->create();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", [
            'public_url' => 'https://pve-07.example.com:2115',
        ])
        ->assertCreated()
        ->assertJsonPath('data.compatibility', 'compatible');

    $anchor->refresh();

    expect($anchor->isApproved())->toBeTrue()
        ->and($anchor->public_url)->toBe('https://pve-07.example.com:2115')
        ->and($anchor->consoleWebsocketUrl())->toBe('wss://pve-07.example.com:2115/api/v1/console');
});

it('requires an address, because it is the one thing the machine cannot tell us', function () {
    $anchor = Anchor::factory()->pending()->create();

    // It knows which interface it bound to. It cannot know how this panel
    // routes back through whatever NAT or split-horizon DNS sits between.
    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", [])
        ->assertJsonValidationErrors('public_url');

    expect($anchor->refresh()->isApproved())->toBeFalse();
});

it('approves only once', function () {
    $anchor = Anchor::factory()->pending()->create();
    $url = 'https://pve-07.example.com:2115';

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", ['public_url' => $url])
        ->assertCreated();

    $approvedAt = $anchor->refresh()->approved_at;

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", ['public_url' => 'https://elsewhere.example.com'])
        ->assertJsonValidationErrors('anchor');

    // Not merely refused -- unchanged. A second approval must not be a back
    // door to rewriting the address the console dials.
    expect($anchor->refresh()->approved_at->eq($approvedAt))->toBeTrue()
        ->and($anchor->public_url)->toBe($url);
});

it('refuses to approve something that never introduced itself', function () {
    $anchor = Anchor::factory()->create(['approved_at' => null, 'enrolled_at' => null]);

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", ['public_url' => 'https://x.example.com'])
        ->assertJsonValidationErrors('anchor');
});

it('lets the operator rename a machine whose hostname means nothing to them', function () {
    $anchor = Anchor::factory()->pending()->create();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", [
            'public_url' => 'https://pve-07.example.com:2115',
            'name' => 'Rack 4 · Node 7',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rack 4 · Node 7');
});

it('reports a machine waiting for a decision as waiting, not as broken', function () {
    $anchor = Anchor::factory()->pending()->create();

    // It is heartbeating perfectly well. Calling it unreachable would send an
    // operator to the network instead of to the queue holding the decision.
    expect($anchor->compatibility())->toBe(AnchorCompatibility::PENDING_APPROVAL);

    $this->actingAs(admin())
        ->getJson("/api/admin/anchors/{$anchor->id}")
        ->assertOk()
        ->assertJsonPath('data.compatibility', 'pending_approval')
        ->assertJsonPath('data.publicUrl', null)
        ->assertJsonPath('data.reportedFacts.hostname', 'pve-new.example.com');
});

it('will not mint a console session for an unapproved anchor', function () {
    [$user, , $node, $server] = createServerModel();
    $anchor = Anchor::factory()->pending()->create();
    $node->update(['anchor_id' => $anchor->id]);

    // Driven at the service rather than through the client route: the gate
    // lives here, and the wording is the point of the assertion -- an operator
    // told "unreachable" goes to look at the network instead of the queue.
    expect(fn () => app(AnchorSessionService::class)->create(
        $server->refresh(),
        $user,
        ConsoleType::NOVNC,
    ))->toThrow(ConflictHttpException::class, "Anchor {$anchor->name} is waiting to be approved.");
});

it('lists the approval queue on its own', function () {
    Anchor::factory()->pending()->create(['name' => 'newcomer']);
    Anchor::factory()->enrolled()->create(['name' => 'established']);

    $pending = $this->actingAs(admin())
        ->getJson('/api/admin/anchors?filter[pending]=1')
        ->assertOk();

    expect($pending->json('items'))->toHaveCount(1)
        ->and($pending->json('items.0.name'))->toBe('newcomer');

    $settled = $this->actingAs(admin())
        ->getJson('/api/admin/anchors?filter[pending]=0')
        ->assertOk();

    expect($settled->json('items'))->toHaveCount(1)
        ->and($settled->json('items.0.name'))->toBe('established');
});

it('treats every Anchor that predates self-registration as already approved', function () {
    // The upgrade backfills `approved_at` from `created_at`, because filling in
    // the form was the approval. Anything else would strand a working fleet
    // behind a queue the moment they upgraded.
    $anchor = Anchor::factory()->enrolled()->create();

    expect($anchor->isApproved())->toBeTrue()
        ->and($anchor->compatibility())->toBe(AnchorCompatibility::COMPATIBLE);
});

it('records the approval against the machine it let in', function () {
    $anchor = Anchor::factory()->pending()->create();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", [
            'public_url' => 'https://pve-07.example.com:2115',
        ])
        ->assertCreated();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_ANCHOR_APPROVED)->sole();

    expect($log->subject->is($anchor))->toBeTrue()
        ->and($log->properties['public_url'])->toBe('https://pve-07.example.com:2115')
        ->and($log->properties['hostname'])->toBe('pve-new.example.com');
});

it('is not something a non-admin can do', function () {
    $anchor = Anchor::factory()->pending()->create();

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->postJson("/api/admin/anchors/{$anchor->id}/approve", [
            'public_url' => 'https://pve-07.example.com:2115',
        ])
        ->assertForbidden();

    expect($anchor->refresh()->isApproved())->toBeFalse();
});
