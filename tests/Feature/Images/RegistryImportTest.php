<?php

use App\Enums\Image\ImageSource;
use App\Enums\Image\RegistryImportStatus;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\User;
use App\Services\Images\OsProfiles;
use App\Services\Images\RegistryImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const REGISTRY_URL = 'https://registry.test/registry.json';

/**
 * The published catalogue, optionally with edits.
 *
 * @param  callable(array): array|null  $edit
 */
function registryFixture(?callable $edit = null): array
{
    $raw = json_decode(
        file_get_contents(base_path('tests/Fixtures/Images/registry.json')),
        true,
    );

    return $edit ? $edit($raw) : $raw;
}

/**
 * Serve a catalogue at the configured URL, and clear whatever the last test
 * left cached under it.
 *
 * @param  callable(array): array|null  $edit
 */
function fakeRegistry(?callable $edit = null): void
{
    config()->set('convoy.registry.url', REGISTRY_URL);
    Cache::forget('images.registry.'.sha1(REGISTRY_URL));

    // The stub answers from whatever the test last put here rather than from a
    // response captured at fake time. `Http::fake()` appends its stubs and
    // matches the first one registered for a URL, so a second call naming the
    // same URL would go on serving the first document forever.
    $GLOBALS['registryDocument'] = registryFixture($edit);

    Http::fake([REGISTRY_URL => fn () => Http::response($GLOBALS['registryDocument'])]);
}

/** The same catalogue with Debian rebuilt: a later date and different bytes. */
function rebuiltDebian(array $raw): array
{
    $raw['groups'][0]['templates'][0]['built_at'] = '2026-10-01T05:28:06Z';
    $raw['groups'][0]['templates'][0]['disks'][0]['sha256'] = str_repeat('4', 64);

    return $raw;
}

beforeEach(function () {
    fakeRegistry();
});

it('reads the catalogue into the panel’s own terms', function () {
    $catalog = app(RegistryImportService::class)->catalog();

    expect($catalog->groups)->toHaveCount(2);

    $debian = collect($catalog->groups)->first()->templates->toCollection()->first();

    expect($debian->slug)->toBe('debian-13-amd64')
        ->and($debian->display)->toBe('Debian 13')
        ->and($debian->ostype)->toBe('l26')
        // The build time, carried as itself. The panel no longer derives a
        // version from it: truncating it to a date is what made two builds of
        // one recipe on one day collide.
        ->and($debian->builtAt)->toBe('2026-09-04T05:28:06Z')
        ->and($debian->status)->toBe(RegistryImportStatus::NEW)
        // `5G` is 5 GiB, the way qemu-img writes it. Reading it as 5 * 10^9
        // would put the plan floor below the disk that actually arrives.
        ->and($debian->minimumDisk)->toBe(5 * 1024 ** 3)
        ->and($debian->size)->toBe(901424128)
        ->and($debian->minimumCores)->toBe(1)
        ->and($debian->minimumMemory)->toBe(2048);
});

it('keeps the captured profile but drops what Proxmox would refuse', function () {
    $catalog = app(RegistryImportService::class)->catalog();
    $debian = collect($catalog->groups)->first()->templates->toCollection()->first();

    expect($debian->hardware)
        ->toHaveKeys(['agent', 'bios', 'cpu', 'machine', 'scsihw', 'serial0'])
        // Its own column, and passed explicitly at build time.
        ->not->toHaveKey('ostype')
        // Only the NIC model, which is not a `qm create` parameter.
        ->not->toHaveKey('net_model')
        // The panel composes cloud-init's user itself.
        ->not->toHaveKey('ciuser');

    $windows = collect($catalog->groups)->last()->templates->toCollection()->first();

    // A TPM is allocated as a `tpmstate0` volume, not a `tpm` setting -- but it
    // is kept, not dropped. The catalogue ships no `tpmstate0` disk on purpose
    // (a shared state volume would give every guest the same endorsement key),
    // so this key is the only record that the image needs one at all. It is
    // stripped on the way out to Proxmox instead, by OsProfiles::proxmoxKeys().
    expect($windows->hardware['tpm'])->toBe('v2.0')
        ->and($windows->hardware['bios'])->toBe('ovmf')
        ->and(OsProfiles::proxmoxKeys($windows->hardware))->not->toHaveKey('tpm');
});

it('carries the disk settings the image was built with', function () {
    $catalog = app(RegistryImportService::class)->catalog();
    $windows = collect($catalog->groups)->last()->templates->toCollection()->first();
    $disks = $windows->disks->toCollection();

    expect($disks->first()->options)->toBe(['discard' => 'on'])
        ->and($disks->last()->options)->toBe([
            'efitype' => '4m',
            'pre-enrolled-keys' => 1,
            'ms-cert' => '2023k',
        ])
        // Only the system disk declares a provisioned size; a varstore is a raw
        // file that is its own.
        ->and($disks->last()->virtualSize)->toBe(540672);
});

it('refuses a catalogue it does not know how to read', function () {
    fakeRegistry(function (array $raw) {
        $raw['schema_version'] = '1';

        return $raw;
    });

    $this->actingAs(admin())
        ->getJson('/api/admin/images/registry')
        ->assertStatus(422);
});

it('imports a template as a group, a definition and a version', function () {
    $response = $this->actingAs(admin())->postJson('/api/admin/images/registry/imports', [
        'templates' => ['windows-server-2025-amd64'],
    ]);

    $response->assertSuccessful();

    $group = ImageGroup::firstWhere('registry_slug', 'windows-server');
    $definition = ImageDefinition::firstWhere('registry_slug', 'windows-server-2025-amd64');

    expect($group->name)->toBe('Windows Server')
        ->and($group->icon)->toBe('windows')
        ->and($definition->image_group_id)->toBe($group->id)
        ->and($definition->name)->toBe('Windows Server 2025 Datacenter')
        ->and($definition->ostype)->toBe('win11')
        ->and($definition->minimum_cores)->toBe(2)
        ->and($definition->minimum_memory)->toBe(4096);

    $version = $definition->latestVersion();

    // The panel's own count of the builds it holds, not a claim about the
    // publisher's release history -- the catalogue publishes no version.
    expect($version->version)->toBe('1')
        ->and($version->built_at->toIso8601String())->toBe('2026-08-04T16:53:31+00:00')
        ->and($version->source)->toBe(ImageSource::REGISTRY)
        ->and($version->diskSet())->toHaveCount(2)
        // A catalogue hosts its own files, so an import is a link: no bytes
        // pass through the panel and the hash is already published.
        ->and($version->systemDisk()->url)->toStartWith('https://')
        ->and($version->systemDisk()->path)->toBeNull()
        ->and($version->systemDisk()->sha256)->toBe(str_repeat('2', 64))
        ->and($version->minimumDiskSize())->toBe(32 * 1024 ** 3);
});

it('adds nothing when the same build is imported twice', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $response = $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ]);

    $response->assertSuccessful()->assertJsonPath('data.0.created', false);

    expect(ImageDefinition::where('registry_slug', 'debian-13-amd64')->count())->toBe(1)
        ->and(ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64')->versions()->count())->toBe(1);
});

it('adds a rebuilt template as a newer version of the same image', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    fakeRegistry(rebuiltDebian(...));

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful()->assertJsonPath('data.0.created', true);

    $definition = ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64');

    expect(ImageDefinition::where('registry_slug', 'debian-13-amd64')->count())->toBe(1)
        ->and($definition->versions()->count())->toBe(2)
        // The second build the panel has taken of this image, and the build
        // time it carries is the publisher's rather than the panel's.
        ->and($definition->latestVersion()->version)->toBe('2')
        ->and($definition->latestVersion()->built_at->toIso8601String())->toBe('2026-10-01T05:28:06+00:00');
});

it('says which catalogue entries are held and which have been rebuilt', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $this->actingAs($admin)
        ->getJson('/api/admin/images/registry')
        ->assertOk()
        ->assertJsonPath('data.groups.0.templates.0.status', RegistryImportStatus::IMPORTED->value)
        ->assertJsonPath('data.groups.0.templates.0.importedVersion', '1')
        ->assertJsonPath('data.groups.1.templates.0.status', RegistryImportStatus::NEW->value);

    fakeRegistry(rebuiltDebian(...));

    $this->actingAs($admin)
        ->getJson('/api/admin/images/registry?refresh=1')
        ->assertOk()
        ->assertJsonPath(
            'data.groups.0.templates.0.status',
            RegistryImportStatus::UPDATE_AVAILABLE->value,
        );
});

it('claims an image group the operator already made rather than making a second', function () {
    $existing = ImageGroup::create(['name' => 'Debian']);

    $this->actingAs(admin())->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    expect(ImageGroup::where('name', 'Debian')->count())->toBe(1)
        ->and($existing->refresh()->registry_slug)->toBe('debian');
});

it('leaves the operator’s own edits alone when a rebuild is imported', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $definition = ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64');
    $definition->update(['name' => 'Debian (house build)', 'is_admin_only' => true]);

    fakeRegistry(rebuiltDebian(...));

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    expect($definition->refresh()->name)->toBe('Debian (house build)')
        ->and($definition->is_admin_only)->toBeTrue();
});

it('404s a template the catalogue does not list', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/images/registry/imports', ['templates' => ['not-a-template']])
        ->assertNotFound();
});

it('is closed to anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/admin/images/registry')
        ->assertForbidden();
});

/**
 * The defect this replaced: the version was the build date, so a recipe built
 * twice in one day collided on a column that is unique per definition, and the
 * collision was resolved by bumping the patch. That produced `2026.9.5` for a
 * build made on the 4th, and took the number a real build on the 5th would want.
 */
it('numbers a same-day rebuild without claiming it was built the next day', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    // Same day, different bytes: a genuinely different build.
    fakeRegistry(function (array $raw) {
        $raw['groups'][0]['templates'][0]['built_at'] = '2026-09-04T19:12:44Z';
        $raw['groups'][0]['templates'][0]['disks'][0]['sha256'] = str_repeat('7', 64);

        return $raw;
    });

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful()->assertJsonPath('data.0.created', true);

    $definition = ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64');
    $versions = $definition->versions()->orderBy('version_major')->get();

    expect($versions->pluck('version')->all())->toBe(['1', '2'])
        // Both build times survive intact, and both are the 4th.
        ->and($versions->map(fn ($v) => $v->built_at->toIso8601String())->all())->toBe([
            '2026-09-04T05:28:06+00:00',
            '2026-09-04T19:12:44+00:00',
        ]);
});

it('keeps two live builds on distinct numbers', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    fakeRegistry(rebuiltDebian(...));

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $definition = ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64');

    expect($definition->versions()->pluck('version')->sort()->values()->all())->toBe(['1', '2']);

    // Deleting the newest build frees its number, which is safe precisely
    // because a deployment records its version by foreign key rather than by
    // label -- and cannot be deleted out from under one at all.
    $definition->versions()->where('version', '2')->delete();

    fakeRegistry(function (array $raw) {
        $raw['groups'][0]['templates'][0]['built_at'] = '2026-11-02T05:28:06Z';
        $raw['groups'][0]['templates'][0]['disks'][0]['sha256'] = str_repeat('9', 64);

        return $raw;
    });

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $live = $definition->refresh()->versions()->pluck('version')->sort()->values()->all();

    expect($live)->toBe(['1', '2'])
        ->and(count($live))->toBe(count(array_unique($live)));
});

it('fills in the build time of a row imported before it was recorded', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful();

    $definition = ImageDefinition::firstWhere('registry_slug', 'debian-13-amd64');

    // What the migration leaves behind: the build time was never stored, and it
    // cannot be guessed back out of the old date-derived label.
    $definition->versions()->update(['built_at' => null]);

    // An unchanged build is matched by content and creates no row, so this is
    // the only chance to repair it.
    $this->actingAs($admin)->postJson('/api/admin/images/registry/imports', [
        'templates' => ['debian-13-amd64'],
    ])->assertSuccessful()->assertJsonPath('data.0.created', false);

    expect($definition->refresh()->latestVersion()->built_at->toIso8601String())
        ->toBe('2026-09-04T05:28:06+00:00');
});
