<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\ImageGroup;

it('records only the fields an image group update changed', function () {
    $group = ImageGroup::create(['name' => 'Before', 'is_admin_only' => false]);

    $this->travel(5)->seconds();

    $this->actingAs(admin())
        ->putJson("/api/admin/image-groups/{$group->uuid}", [
            'name' => 'After',
            'description' => null,
            'icon' => null,
            'is_admin_only' => false,
        ])
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_IMAGE_GROUP_UPDATED)->sole()->properties['changed'])
        ->toBe(['name']);
});
