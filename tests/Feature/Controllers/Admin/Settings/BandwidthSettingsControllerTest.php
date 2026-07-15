<?php

use App\Models\User;
use App\Settings\BandwidthSettings;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
});

it('returns the global bandwidth defaults', function () {
    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/bandwidth')
        ->assertOk()
        ->assertJsonPath('data.overagePenalty.action', 'throttle')
        ->assertJsonPath('data.overagePenalty.rate', 1_000_000);
});

it('updates the global overage penalty', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/bandwidth', [
            'overage_penalty' => ['action' => 'throttle', 'rate' => 25_000_000],
        ])
        ->assertOk()
        ->assertJsonPath('data.overagePenalty.rate', 25_000_000);

    expect(app(BandwidthSettings::class)->overage_rate)->toBe(25_000_000);
});

it('keeps the stored rate when switching to disconnect', function () {
    // The rate isn't part of a disconnect penalty, but throwing it away would
    // lose the operator's figure the moment they flipped back to throttle.
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/bandwidth', [
            'overage_penalty' => ['action' => 'disconnect'],
        ])
        ->assertOk()
        ->assertJsonPath('data.overagePenalty.action', 'disconnect');

    $settings = app(BandwidthSettings::class);
    expect($settings->overage_action)->toBe('disconnect')
        ->and($settings->overage_rate)->toBe(1_000_000);
});

it('requires a rate when throttling', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/bandwidth', [
            'overage_penalty' => ['action' => 'throttle'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('overage_penalty.rate');
});

it('rejects an unknown action', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/bandwidth', [
            'overage_penalty' => ['action' => 'explode', 'rate' => 1_000_000],
        ])
        ->assertStatus(422);
});

it('forbids a non-admin', function () {
    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)
        ->getJson('/api/admin/settings/bandwidth')
        ->assertForbidden();
});
