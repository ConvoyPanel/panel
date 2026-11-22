<?php

use App\Enums\Admin\AdminPermission;
use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\Provisioner;

beforeEach(function () {
    $this->admin = admin();
});

function provisionerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Docker',
        'description' => 'Installs Docker Engine.',
        'script' => "#!/bin/sh\ncurl -fsSL https://get.docker.com | sh\n",
        'fields' => [
            ['key' => 'domain', 'label' => 'Domain', 'required' => true],
            ['key' => 'admin_email', 'label' => 'Admin email', 'required' => false],
        ],
    ], $overrides);
}

it('creates a provisioner', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/admin/provisioners', provisionerPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'Docker')
        ->assertJsonPath('data.fields.0.key', 'domain')
        ->assertJsonPath('data.fields.0.required', true)
        ->assertJsonPath('data.fields.1.required', false)
        ->assertJsonPath('data.imageCount', 0);

    $provisioner = Provisioner::query()->sole();

    expect($provisioner->fields)->toBe([
        ['key' => 'domain', 'label' => 'Domain', 'required' => true],
        ['key' => 'admin_email', 'label' => 'Admin email', 'required' => false],
    ]);

    expect(AuditLog::query()->where('event', AuditEvent::ADMIN_PROVISIONER_CREATED)->exists())->toBeTrue();
});

it('lists, shows, updates and deletes provisioners', function () {
    $provisioner = Provisioner::factory()->create(['name' => 'Monitoring']);

    $this->actingAs($this->admin)
        ->getJson('/api/admin/provisioners')
        ->assertOk()
        ->assertJsonPath('data.0.uuid', $provisioner->uuid);

    $this->actingAs($this->admin)
        ->getJson("/api/admin/provisioners/{$provisioner->uuid}")
        ->assertOk()
        ->assertJsonPath('data.script', "#!/bin/sh\necho hello\n");

    $this->actingAs($this->admin)
        ->putJson("/api/admin/provisioners/{$provisioner->uuid}", provisionerPayload(['name' => 'Monitoring']))
        ->assertOk()
        ->assertJsonPath('data.description', 'Installs Docker Engine.');

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/provisioners/{$provisioner->uuid}")
        ->assertNoContent();

    expect(Provisioner::query()->exists())->toBeFalse();
});

it('records only the fields a provisioner update changed', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/provisioners', provisionerPayload())->assertCreated();
    $provisioner = Provisioner::query()->sole();

    $this->travel(5)->seconds();

    $this->actingAs($this->admin)
        ->putJson("/api/admin/provisioners/{$provisioner->uuid}", provisionerPayload(['name' => 'Monitoring']))
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_PROVISIONER_UPDATED)->sole()->properties['changed'])
        ->toBe(['name']);
});

it('refuses field keys that cannot be an environment variable', function (string $key) {
    $this->actingAs($this->admin)
        ->postJson('/api/admin/provisioners', provisionerPayload([
            'fields' => [['key' => $key, 'label' => 'Bad', 'required' => false]],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fields.0.key');
})->with([
    'uppercase' => 'Domain',
    'leading digit' => '1domain',
    'dash' => 'admin-email',
    'space' => 'a b',
    'shell' => 'a$(id)',
    'too long' => str_repeat('a', 33),
]);

it('refuses two fields with the same key', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/admin/provisioners', provisionerPayload([
            'fields' => [
                ['key' => 'domain', 'label' => 'Domain', 'required' => true],
                ['key' => 'domain', 'label' => 'Again', 'required' => false],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fields.1.key');
});

it('refuses a duplicate name', function () {
    Provisioner::factory()->create(['name' => 'Docker']);

    $this->actingAs($this->admin)
        ->postJson('/api/admin/provisioners', provisionerPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('is governed by the image permissions', function () {
    $reader = operator([AdminPermission::IMAGE_GROUPS_READ]);
    $manager = operator([AdminPermission::IMAGE_GROUPS_MANAGE]);
    $other = operator([AdminPermission::SERVERS_MANAGE]);

    $this->actingAs($reader)->getJson('/api/admin/provisioners')->assertOk();
    $this->actingAs($reader)->postJson('/api/admin/provisioners', provisionerPayload())->assertForbidden();
    $this->actingAs($other)->getJson('/api/admin/provisioners')->assertForbidden();
    $this->actingAs($manager)->postJson('/api/admin/provisioners', provisionerPayload())->assertCreated();
});

function imageDefinition(string $ostype = 'l26'): ImageDefinition
{
    $group = ImageGroup::create(['name' => 'Linux '.Str::random(4)]);

    return ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Ubuntu 24.04',
        'ostype' => $ostype,
    ]);
}

it('attaches provisioners to an image in order and merges their fields', function () {
    $image = imageDefinition();

    $web = Provisioner::factory()->withFields([
        ['key' => 'domain', 'label' => 'Domain', 'required' => false],
        ['key' => 'email', 'label' => 'Email', 'required' => false],
    ])->create(['name' => 'Web']);
    $mail = Provisioner::factory()->withFields([
        ['key' => 'domain', 'label' => 'Mail domain', 'required' => true],
    ])->create(['name' => 'Mail']);

    $this->actingAs($this->admin)
        ->putJson("/api/admin/image-groups/{$image->group->uuid}/images/{$image->uuid}", [
            'name' => $image->name,
            'ostype' => 'l26',
            'provisioner_uuids' => [$mail->uuid, $web->uuid],
        ])
        ->assertOk()
        ->assertJsonPath('data.provisionerUuids', [$mail->uuid, $web->uuid])
        // One `domain`, named by the first provisioner, required because one
        // of them requires it.
        ->assertJsonPath('data.provisionerFields', [
            ['key' => 'domain', 'label' => 'Mail domain', 'required' => true],
            ['key' => 'email', 'label' => 'Email', 'required' => false],
        ]);

    expect($image->fresh()->provisioners->pluck('uuid')->all())->toBe([$mail->uuid, $web->uuid]);

    // Leaving the key out keeps the list; an empty list clears it.
    $this->actingAs($this->admin)
        ->putJson("/api/admin/image-groups/{$image->group->uuid}/images/{$image->uuid}", [
            'name' => $image->name,
            'ostype' => 'l26',
        ])
        ->assertOk()
        ->assertJsonPath('data.provisionerUuids', [$mail->uuid, $web->uuid]);

    $this->actingAs($this->admin)
        ->putJson("/api/admin/image-groups/{$image->group->uuid}/images/{$image->uuid}", [
            'name' => $image->name,
            'ostype' => 'l26',
            'provisioner_uuids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.provisionerUuids', []);
});

it('refuses provisioners on a Windows image', function () {
    $image = imageDefinition('win11');
    $provisioner = Provisioner::factory()->create();

    $this->actingAs($this->admin)
        ->putJson("/api/admin/image-groups/{$image->group->uuid}/images/{$image->uuid}", [
            'name' => $image->name,
            'ostype' => 'win11',
            'provisioner_uuids' => [$provisioner->uuid],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provisioner_uuids');
});

it('counts the images that run a provisioner', function () {
    $provisioner = Provisioner::factory()->create();
    imageDefinition()->provisioners()->attach($provisioner, ['position' => 0]);
    imageDefinition()->provisioners()->attach($provisioner, ['position' => 0]);

    $this->actingAs($this->admin)
        ->getJson('/api/admin/provisioners')
        ->assertJsonPath('data.0.imageCount', 2);
});

it('records only the fields an image update changed', function () {
    $image = imageDefinition();
    $provisioner = Provisioner::factory()->create();

    $this->travel(5)->seconds();

    $this->actingAs($this->admin)
        ->putJson("/api/admin/image-groups/{$image->group->uuid}/images/{$image->uuid}", [
            'name' => 'Ubuntu 24.04 LTS',
            'ostype' => 'l26',
            'provisioner_uuids' => [$provisioner->uuid],
        ])
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_IMAGE_UPDATED)->sole()->properties['changed'])
        ->toBe(['name', 'provisioners']);
});
