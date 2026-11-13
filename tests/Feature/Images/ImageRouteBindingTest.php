<?php

use App\Enums\Image\ImageDiskRole;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\ImageVersion;

/*
 * The admin API is registered with scopeBindings(), so every route below
 * `/image-groups/{image_group}` resolves its definition (and a version under
 * that) through the parent's relation. These pin that resolution down: the
 * relation exists, and a child under the wrong parent is not found.
 */

/**
 * @return array{0: ImageGroup, 1: ImageDefinition, 2: ImageVersion}
 */
function imageBindingChain(string $name): array
{
    $group = ImageGroup::create(['name' => $name]);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => "{$name} 1",
        'ostype' => 'l26',
    ]);
    $version = $definition->versions()->create([
        'version' => '1.0.0',
        'disks' => [[
            'slot' => 'scsi0',
            'role' => ImageDiskRole::SYSTEM->value,
            'url' => 'https://example.invalid/disk.qcow2',
            'path' => null,
            'sha256' => str_repeat('b', 64),
            'size' => 1024,
            'virtual_size' => 2048,
            'format' => 'qcow2',
        ]],
    ]);

    return [$group, $definition, $version];
}

it('shows a definition through its group', function () {
    [$group, $definition] = imageBindingChain('Debian');

    $this->actingAs(admin())
        ->getJson("/api/admin/image-groups/{$group->uuid}/images/{$definition->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', $definition->uuid);
});

it('lists and shows a definition’s versions', function () {
    [$group, $definition, $version] = imageBindingChain('Debian');
    $base = "/api/admin/image-groups/{$group->uuid}/images/{$definition->uuid}/versions";

    $this->actingAs(admin())
        ->getJson($base)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.uuid', $version->uuid);

    $this->actingAs(admin())
        ->getJson("{$base}/{$version->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', $version->uuid);
});

it('does not find a definition under another group', function () {
    [$group] = imageBindingChain('Debian');
    [, $elsewhere] = imageBindingChain('Ubuntu');

    $this->actingAs(admin())
        ->getJson("/api/admin/image-groups/{$group->uuid}/images/{$elsewhere->uuid}")
        ->assertNotFound();
});

it('does not find a version under another definition', function () {
    [$group, $definition] = imageBindingChain('Debian');
    [, , $elsewhere] = imageBindingChain('Ubuntu');

    $this->actingAs(admin())
        ->getJson("/api/admin/image-groups/{$group->uuid}/images/{$definition->uuid}/versions/{$elsewhere->uuid}")
        ->assertNotFound();
});
