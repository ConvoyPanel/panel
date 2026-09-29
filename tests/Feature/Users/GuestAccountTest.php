<?php

use App\Enums\Server\ServerPermission;
use App\Enums\User\UserType;
use App\Models\AdminRole;
use App\Models\Server;
use App\Models\User;
use App\Settings\PermissionSettings;
use Illuminate\Database\QueryException;

/**
 * Guests are accounts the provider never provisioned. Two things have to hold: an admin can
 * always tell them apart from real customers, and a guest can reach nothing beyond the servers
 * shared with it.
 */
beforeEach(function () {
    fakeProxmox();

    // On for the bulk of these: what a guest can and cannot reach is only a question once the
    // feature is switched on at all. The switch's own behaviour has its own block below.
    app(PermissionSettings::class)->fill(['allow_guest_accounts' => true])->save();

    [$this->owner, , , $this->server] = createServerModel();
    $this->guest = subuser($this->server, [ServerPermission::POWER_START]);
    $this->guest->update(['type' => UserType::GUEST]);
});

it('keeps a guest out of every server but the shared one', function () {
    [, , , $other] = createServerModel();

    $this->actingAs($this->guest)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.uuid', $this->server->uuid);

    $this->actingAs($this->guest)
        ->getJson("/api/client/servers/{$other->uuid}")
        ->assertNotFound();
});

it('keeps a guest out of the admin area entirely', function () {
    $this->actingAs($this->guest)->getJson('/api/admin/overview')->assertForbidden();
    $this->actingAs($this->guest)->getJson('/api/admin/users')->assertForbidden();
});

it('refuses to give a guest an admin role at the database level', function () {
    // Enforced by a CHECK constraint, not only by the request layer: this is the one invariant
    // that turns a shared server into a compromised panel if it is ever missed.
    expect(fn () => $this->guest->forceFill([
        'admin_role_id' => AdminRole::superadmin()->id,
    ])->saveQuietly())->toThrow(QueryException::class);
});

it('refuses to give a guest an admin role through the admin API', function () {
    $this->actingAs(admin())
        ->patchJson("/api/admin/users/{$this->guest->id}", [
            'name' => $this->guest->name,
            'email' => $this->guest->email,
            'type' => 'guest',
            'admin_role_id' => AdminRole::superadmin()->id,
        ])
        ->assertBadRequest();
});

it('refuses to make a guest the owner of a server', function () {
    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$this->server->id}", ['user_id' => $this->guest->id])
        ->assertStatus(422)
        ->assertJsonPath('errors.user_id.0', 'A guest account cannot own a server.');
});

it('refuses to mint an SSO link for a guest', function () {
    // The link signs the operator in *as* the account. There is no billing session for a guest to
    // land in, and one exists only because somebody else shared a server.
    $this->actingAs(admin())
        ->postJson("/api/admin/users/{$this->guest->id}/generate-sso-token")
        ->assertBadRequest();
});

it('separates guests from provisioned accounts in the admin list', function () {
    $admin = admin();

    $emails = fn (string $query) => collect(
        $this->actingAs($admin)->getJson("/api/admin/users?{$query}")->assertOk()->json('items'),
    )->pluck('email')->all();

    expect($emails('filter[type]=guest'))->toBe([$this->guest->email])
        ->and($emails('filter[type]=standard'))->not->toContain($this->guest->email);

    // And the type is on every row, so a list that is not filtered still says which is which.
    $row = collect($this->actingAs($admin)->getJson('/api/admin/users')->json('items'))
        ->firstWhere('email', $this->guest->email);

    expect($row['type'])->toBe('guest');
});

it('counts guests apart from customers on the overview', function () {
    $summary = $this->actingAs(admin())
        ->getJson('/api/admin/overview')
        ->assertOk()
        ->json('data.summary');

    expect($summary['guests'])->toBe(1)
        ->and($summary['users'])->toBe(User::query()->where('type', 'standard')->count());
});

it('promotes a guest to a real account', function () {
    $this->actingAs(admin())
        ->patchJson("/api/admin/users/{$this->guest->id}", [
            'name' => $this->guest->name,
            'email' => $this->guest->email,
            'type' => 'standard',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.type', 'standard');

    expect($this->guest->fresh()->type)->toBe(UserType::STANDARD);
});

it('refuses to demote an account that owns servers', function () {
    $this->actingAs(admin())
        ->patchJson("/api/admin/users/{$this->owner->id}", [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'type' => 'guest',
        ])
        ->assertBadRequest();

    expect($this->owner->fresh()->type)->toBe(UserType::STANDARD);
});

it('keeps the account when its only shared server goes away', function () {
    // Not auto-deleted: that would take the audit trail with it, and the same person is likely to
    // be invited again.
    $this->server->delete();

    expect(User::query()->whereKey($this->guest->id)->exists())->toBeTrue()
        ->and($this->guest->serverShares()->count())->toBe(0);

    $this->actingAs($this->guest)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(0, 'items');
});

describe('the global switch', function () {
    beforeEach(function () {
        app(PermissionSettings::class)->fill(['allow_guest_accounts' => false])->save();
    });

    it('locks existing guests out while it is off', function () {
        // Off means off in both directions. A switch that only stopped new guests would leave an
        // operator with no way to close the door on the ones already through.
        $this->actingAs($this->guest)
            ->getJson("/api/client/servers/{$this->server->uuid}")
            ->assertForbidden();
    });

    it('refuses a password login for a locked-out guest', function () {
        $this->guest->update(['password' => 'Sup3rSecret!']);

        $this->postJson('/api/auth/login', [
            'email' => $this->guest->email,
            'password' => 'Sup3rSecret!',
        ])->assertStatus(422);

        $this->assertGuest();
    });

    it('lets them back in when it is switched on, with their shares intact', function () {
        app(PermissionSettings::class)->fill(['allow_guest_accounts' => true])->save();

        // Shares are kept while the switch is off rather than deleted, so flipping it back
        // restores exactly what was there.
        $this->actingAs($this->guest)
            ->getJson("/api/client/servers/{$this->server->uuid}")
            ->assertOk()
            ->assertJsonPath('data.permissions', ['power.start']);
    });

    it('never touches a provisioned account', function () {
        $this->actingAs($this->owner)
            ->getJson("/api/client/servers/{$this->server->uuid}")
            ->assertOk();
    });
});

it('lets a guest manage its own account', function () {
    // Securing the account is the guest's own job: nothing here is a step towards the panel.
    $this->actingAs($this->guest)
        ->patchJson('/api/client/account/profile', ['name' => 'Renamed'])
        ->assertSuccessful();

    expect($this->guest->fresh()->name)->toBe('Renamed');
});

it('never lets the client server list leak a server it was not shared', function () {
    Server::query()->whereKey($this->server->id)->update(['user_id' => User::factory()->create()->id]);

    // The share survives an owner change made directly in the database; what the guest can see is
    // still exactly the one server, which is what `scopeOwnedBy` is the single source of truth for.
    $this->actingAs($this->guest)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items');
});
