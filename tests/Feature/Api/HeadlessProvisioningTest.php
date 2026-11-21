<?php

use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerPreset;
use App\Models\Storage;
use App\Models\User;
use App\Services\Api\CreateApplicationTokenService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use phpseclib3\Crypt\EC;

/**
 * What an integration needs to create servers and accounts on its own: ids it can find them
 * by again, retries that do not double up, and placement it does not have to work out.
 */
beforeEach(function () {
    Http::fake([
        '*/api2/json/cluster/nextid*' => Http::response(['data' => 12345]),
        // No live storage report, so the disk-space checks fail open.
        '*/api2/json/nodes/*/storage' => Http::response(['data' => []]),
        '*' => Http::response(['data' => 'dummy-upid']),
    ]);
    Bus::fake();

    $admin = User::factory()->create(['root_admin' => true]);
    $this->token = app(CreateApplicationTokenService::class)->handle($admin, 'billing')->plainTextToken;
    $this->owner = User::factory()->create();
});

function provisioningPayload(array $overrides = [], array $limits = []): array
{
    return array_replace([
        'user_id' => test()->owner->id,
        'hostname' => 'web-1',
        'name' => 'Web 1',
        'deferred_os_selection' => true,
        'should_create_vm' => false,
        'start_on_completion' => false,
        'limits' => array_replace([
            'cpu' => 2,
            'memory' => 2 * 1024 * 1024 * 1024,
            'disk' => 20 * 1024 * 1024 * 1024,
            'bandwidth' => -1,
            'addresses_ipv4_count' => 0,
            'addresses_ipv6_count' => 0,
            'backups' => ['count' => -1, 'size' => -1],
        ], $limits),
    ], $overrides);
}

function nodeWithParts(Location $location, array $attributes = []): Node
{
    $node = Node::factory()->for($location)->create($attributes);
    $node->storages()->attach(Storage::factory()->create());
    NetworkInterface::factory()->for($node)->create();

    return $node;
}

describe('external ids', function () {
    it('stores a server external id and finds the server by it', function () {
        $location = Location::factory()->create();
        $node = nodeWithParts($location);

        $this->withToken($this->token)
            ->postJson('/api/application/servers', provisioningPayload(['node_id' => $node->id, 'external_id' => 'whmcs-42']))
            ->assertCreated()
            ->assertJsonPath('data.externalId', 'whmcs-42');

        $this->withToken($this->token)
            ->getJson('/api/application/servers?filter[external_id]=whmcs-42')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.externalId', 'whmcs-42');
    });

    it('refuses a second server with the same external id', function () {
        $location = Location::factory()->create();
        $node = nodeWithParts($location);
        Server::factory()->for($node)->create(['external_id' => 'whmcs-42']);

        $this->withToken($this->token)
            ->postJson('/api/application/servers', provisioningPayload(['node_id' => $node->id, 'external_id' => 'whmcs-42']))
            ->assertJsonValidationErrorFor('external_id');
    });

    it('hides a server external id from its owner', function () {
        $location = Location::factory()->create();
        $node = nodeWithParts($location);
        $server = Server::factory()->for($node)->for($this->owner)->create(['external_id' => 'whmcs-42']);

        $this->actingAs($this->owner)
            ->getJson("/api/client/servers/{$server->uuid}")
            ->assertOk()
            ->assertJsonPath('data.externalId', null);
    });

    it('stores, filters and updates a user external id', function () {
        $this->withToken($this->token)
            ->postJson('/api/application/users', [
                'name' => 'Ada',
                'email' => 'ada@example.test',
                'external_id' => 'client-7',
            ])
            ->assertSuccessful();

        $user = User::query()->where('email', 'ada@example.test')->sole();
        expect($user->external_id)->toBe('client-7');

        $this->withToken($this->token)
            ->getJson('/api/application/users?filter[external_id]=client-7')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.externalId', 'client-7');

        // Left alone when an update does not mention it.
        $this->withToken($this->token)
            ->putJson("/api/application/users/{$user->id}", ['name' => 'Ada L', 'email' => 'ada@example.test'])
            ->assertOk()
            ->assertJsonPath('data.externalId', 'client-7');

        $this->withToken($this->token)
            ->putJson("/api/application/users/{$user->id}", ['name' => 'Ada L', 'email' => 'ada@example.test', 'external_id' => null])
            ->assertOk()
            ->assertJsonPath('data.externalId', null);
    });
});

describe('idempotency keys', function () {
    it('replays the first response to a retried create', function () {
        $key = (string) Str::uuid();

        $first = $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', ['short_code' => 'fra', 'description' => 'Frankfurt'])
            ->assertCreated();

        $second = $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', ['short_code' => 'fra', 'description' => 'Frankfurt'])
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true');

        expect($second->json())->toBe($first->json())
            ->and(Location::query()->where('short_code', 'fra')->count())->toBe(1);
    });

    it('refuses a key reused for a different request', function () {
        $key = (string) Str::uuid();

        $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', ['short_code' => 'fra'])
            ->assertCreated();

        $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', ['short_code' => 'ams'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'idempotency_key_reused');
    });

    it('does not remember a request that failed', function () {
        $key = (string) Str::uuid();

        $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', [])
            ->assertUnprocessable();

        // Same key, corrected body: the failed attempt did nothing, so this one runs.
        $this->withToken($this->token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/application/locations', ['short_code' => 'fra'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotent-Replayed');
    });

    it('keeps keys apart between tokens', function () {
        $admin = User::factory()->create(['root_admin' => true]);
        $other = app(CreateApplicationTokenService::class)->handle($admin, 'other')->plainTextToken;

        $this->withToken($this->token)->withHeader('Idempotency-Key', 'shared')
            ->postJson('/api/application/locations', ['short_code' => 'fra'])
            ->assertCreated();

        // The test client keeps the first request's resolved user; a real second client would not.
        $this->app['auth']->forgetGuards();

        $this->withToken($other)->withHeader('Idempotency-Key', 'shared')
            ->postJson('/api/application/locations', ['short_code' => 'ams'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotent-Replayed');
    });
});

describe('placement', function () {
    it('picks the emptiest node in a location, with its storage and bridge', function () {
        $location = Location::factory()->create();
        $busy = nodeWithParts($location);
        Server::factory()->for($busy)->create(['memory' => 60 * 1024 * 1024 * 1024]);
        $idle = nodeWithParts($location);

        $this->withToken($this->token)
            ->postJson('/api/application/servers', provisioningPayload(['location_id' => $location->id]))
            ->assertCreated()
            ->assertJsonPath('data.nodeId', $idle->id);

        $server = Server::query()->latest('id')->first();
        expect($server->storage_id)->toBe($idle->storages()->first()->id)
            ->and($server->network_interface_id)->toBe($idle->networkInterfaces()->first()->id);
    });

    it('says so when no node in the location has room', function () {
        $location = Location::factory()->create();
        nodeWithParts($location, ['cpu_count' => 1]);

        $this->withToken($this->token)
            ->postJson('/api/application/servers', provisioningPayload(['location_id' => $location->id]))
            ->assertJsonValidationErrors(['location_id' => 'No node in this location has room for this server.'])
            ->assertJsonMissingValidationErrors(['node_id', 'storage_id', 'limits.network_interface_id']);
    });

    it('fills in the storage and bridge for a named node', function () {
        $node = nodeWithParts(Location::factory()->create());

        $this->withToken($this->token)
            ->postJson('/api/application/servers', provisioningPayload(['node_id' => $node->id]))
            ->assertCreated();

        expect(Server::query()->latest('id')->first()->storage_id)->toBe($node->storages()->first()->id);
    });
});

describe('presets', function () {
    it('fills in the limits a create leaves out, converting the form units', function () {
        $node = nodeWithParts(Location::factory()->create());
        $preset = ServerPreset::factory()->create([
            'settings' => [
                'node_id' => $node->id,
                'cpu' => 4,
                'memory' => 4096,
                'disk' => 40960,
                'bandwidth' => -1,
                'backup_count' => 3,
                'backup_size' => 10240,
                'speed_limit' => 100,
            ],
        ]);

        $payload = provisioningPayload(['server_preset_uuid' => $preset->uuid]);
        unset($payload['limits']);
        $payload['limits'] = ['addresses_ipv4_count' => 0, 'addresses_ipv6_count' => 0, 'cpu' => 2];

        $this->withToken($this->token)
            ->postJson('/api/application/servers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.nodeId', $node->id);

        $server = Server::query()->latest('id')->first();
        expect($server->cpu)->toBe(2) // the request wins
            ->and($server->memory)->toBe(4096 * 1024 * 1024)
            ->and($server->disk)->toBe(40960 * 1024 * 1024)
            ->and($server->backup_count_limit)->toBe(3)
            ->and($server->backup_size_limit)->toBe(10240 * 1024 * 1024)
            ->and($server->speed_limit)->toBe(100_000_000);
    });
});

it('installs ssh keys with the build and lets them stand in for a password', function () {
    $node = nodeWithParts(Location::factory()->create());
    $image = installableImage();
    $key = EC::createKey('Ed25519')->getPublicKey()->toString('OpenSSH');

    $this->withToken($this->token)
        ->postJson('/api/application/servers', provisioningPayload([
            'node_id' => $node->id,
            'vmid' => 12345,
            'deferred_os_selection' => false,
            'should_create_vm' => true,
            'image_uuid' => $image->uuid,
            'ssh_keys' => [$key],
        ]))
        ->assertCreated();

    $deployment = Server::query()->latest('id')->first()->deployments()->sole();
    expect($deployment->steps()->pluck('name')->all())
        ->toContain('update-ssh-keys')
        ->not->toContain('update-password');
});

it('rejects something that is not an ssh key', function () {
    $node = nodeWithParts(Location::factory()->create());

    $this->withToken($this->token)
        ->postJson('/api/application/servers', provisioningPayload([
            'node_id' => $node->id,
            'ssh_keys' => ['not a key'],
        ]))
        ->assertJsonValidationErrorFor('ssh_keys.0');
});
