<?php

use App\Actions\Server\BuildServerAction;
use App\Enums\Provisioner\ProvisioningStatus;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Service\Provisioner\ProvisioningException;
use App\Jobs\Provisioner\CleanUpProvisioningRunJob;
use App\Jobs\Server\PrepareProvisionersJob;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Deployment;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\Provisioner;
use App\Models\ProvisioningRun;
use App\Services\Provisioners\ProvisioningService;
use App\Support\Anchor\AnchorSnippetProtocol;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    [$this->owner, , $this->node, $this->server] = createServerModel();

    $this->node->forceFill([
        'agent_uuid' => (string) Str::uuid(),
        'agent_secret' => str_repeat('k', 32),
        'agent_public_url' => 'https://anchor.example.com',
        'agent_enrolled_at' => now(),
        'agent_last_seen_at' => now(),
        'agent_protocol_min' => 1,
        'agent_protocol_max' => 1,
        'agent_capabilities' => [AnchorSnippetProtocol::CAPABILITY],
    ])->save();

    $this->storage = $this->node->storages()->first();
    $this->storage->update(['name' => 'local', 'pve_content' => 'images,import,snippets']);

    $block = AddressBlock::factory()->for(AddressBlockGroup::factory(), 'addressBlockGroup')->create([
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
    $address = Address::factory()->for($block)->create(['ip' => '192.0.2.10', 'server_id' => $this->server->id]);
    $this->server->forceFill([
        'primary_ipv4_address_id' => $address->id,
        'hostname' => 'web.example.com',
        'lifecycle' => ServerLifecycle::READY,
    ])->save();

    $group = ImageGroup::create(['name' => 'Ubuntu']);
    $this->image = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Ubuntu 24.04',
        'ostype' => 'l26',
    ]);
    $this->image->versions()->create([
        'version' => '1.0.0',
        'disks' => [[
            'slot' => 'scsi0',
            'role' => 'system',
            'url' => 'https://example.invalid/disk.qcow2',
            'path' => null,
            'sha256' => str_repeat('a', 64),
            'size' => 1024,
            'virtual_size' => 2048,
            'format' => 'qcow2',
        ]],
    ]);

    $this->web = Provisioner::factory()->withFields([
        ['key' => 'domain', 'label' => 'Domain', 'required' => true],
    ])->create(['name' => 'Web', 'script' => "#!/bin/sh\necho web\n"]);
    $this->agent = Provisioner::factory()->create(['name' => 'Agent', 'script' => "#!/bin/sh\necho agent\n"]);

    $this->image->provisioners()->attach($this->agent, ['position' => 1]);
    $this->image->provisioners()->attach($this->web, ['position' => 0]);

    $this->deployment = Deployment::create([
        'server_id' => $this->server->id,
        'image_definition_id' => $this->image->id,
        'image_version_id' => $this->image->latestVersion()->id,
        'type' => DeploymentType::REINSTALL,
        'status' => DeploymentStatus::RUNNING,
        'start_on_completion' => true,
        'requested_at' => now(),
    ]);
});

/** The claims of the bearer token on a recorded request. */
function bearerClaims(Request $request): array
{
    $token = Str::after($request->header('Authorization')[0], 'Bearer ');

    return json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
}

/**
 * The files a snippet writes, keyed by path.
 *
 * @return array<string, string>
 */
function snippetFiles(string $snippet): array
{
    preg_match_all('/- path: (\S+)\n    permissions: \'\d+\'\n    encoding: b64\n    content: (\S+)/', $snippet, $matches, PREG_SET_ORDER);

    return collect($matches)->mapWithKeys(fn (array $match) => [$match[1] => base64_decode($match[2])])->all();
}

function fakeNode(): void
{
    Http::fake([
        'https://anchor.example.com/api/v1/snippets/*' => function (Request $request) {
            return $request->method() === 'DELETE'
                ? Http::response(['deleted' => true])
                : Http::response(['volume' => 'local:snippets/x.yaml', 'sha256' => hash('sha256', $request->body()), 'size' => strlen($request->body())]);
        },
        '*/qemu/*/config*' => function (Request $request) {
            return $request->method() === 'GET'
                ? Http::response(['data' => test()->guestConfig ?? []])
                : Http::response(['data' => null]);
        },
    ]);
}

it('writes the snippet before pointing cicustom at it', function () {
    fakeNode();

    $run = app(ProvisioningService::class)->prepare($this->deployment, ['domain' => 'example.com']);

    $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
    $write = $requests->first(fn (Request $r) => $r->method() === 'PUT');
    $config = $requests->first(fn (Request $r) => $r->method() === 'POST' && str_contains($r->url(), '/config'));

    expect($requests->search($write))->toBeLessThan($requests->search($config))
        ->and($write->url())->toBe("https://anchor.example.com/api/v1/snippets/{$run->snippet}")
        ->and($config->data())->toBe(['cicustom' => "vendor=local:snippets/{$run->snippet}"]);

    // The token names this exact write, down to the bytes.
    $claims = bearerClaims($write);
    expect($claims['snippet'])->toBe([
        'action' => 'write',
        'storage' => 'local',
        'name' => $run->snippet,
        'sha256' => hash('sha256', $write->body()),
    ])->and($claims['aud'])->toBe($this->node->agent_uuid);

    expect($run->status)->toBe(ProvisioningStatus::PENDING)
        ->and($run->storage)->toBe('local')
        ->and($run->snippet)->toBe("convoy-provisioner-{$run->uuid}.yaml")
        ->and($run->deadline_at)->not->toBeNull()
        ->and($run->provisioners)->toEqual([
            ['uuid' => $this->web->uuid, 'name' => 'Web'],
            ['uuid' => $this->agent->uuid, 'name' => 'Agent'],
        ]);

    $files = snippetFiles($write->body());
    $dir = '/var/lib/convoy/provisioners';

    expect($write->body())->toStartWith("#cloud-config\n")
        ->and($files["{$dir}/order"])->toBe("{$this->web->uuid}\n{$this->agent->uuid}\n")
        ->and($files["{$dir}/scripts/{$this->web->uuid}"])->toBe("#!/bin/sh\necho web\n")
        ->and($files["{$dir}/environment"])->toContain("CONVOY_FIELD_DOMAIN=example.com\n")
        ->and($files["{$dir}/environment"])->toContain("CONVOY_HOSTNAME=web.example.com\n")
        ->and($files["{$dir}/environment"])->toContain("CONVOY_IPV4=192.0.2.10\n")
        ->and($files["{$dir}/environment"])->toContain("CONVOY_IPV4_GATEWAY=192.0.2.1\n")
        ->and($files["{$dir}/environment"])->toContain("CONVOY_IPV6=\n")
        ->and($files["{$dir}/callback"])->toStartWith(route('provisioning.report')."\n");

    // Only the hash is kept, and it is the hash of the token in the snippet.
    $token = explode("\n", $files["{$dir}/callback"])[1];
    expect($run->token_hash)->toBe(hash('sha256', $token));
});

it('reuses the run when the step is retried, with a fresh token', function () {
    fakeNode();
    $service = app(ProvisioningService::class);

    $first = $service->prepare($this->deployment, ['domain' => 'example.com']);
    $second = $service->prepare($this->deployment, ['domain' => 'example.com']);

    expect($second->id)->toBe($first->id)
        ->and($second->snippet)->toBe($first->snippet)
        ->and($second->token_hash)->not->toBe($first->token_hash)
        ->and(ProvisioningRun::count())->toBe(1);
});

it('starts the clock at the first start when the build does not start the server', function () {
    fakeNode();
    $this->deployment->update(['start_on_completion' => false]);

    $run = app(ProvisioningService::class)->prepare($this->deployment, ['domain' => 'example.com']);
    expect($run->deadline_at)->toBeNull();

    ProvisioningRun::startClockFor($this->server);
    expect($run->fresh()->deadline_at)->not->toBeNull();
});

it('refuses a node whose Anchor cannot write snippets', function () {
    Http::fake();
    $this->node->forceFill(['agent_capabilities' => ['console.qemu.vnc']])->save();

    app(ProvisioningService::class)->prepare($this->deployment->fresh(), ['domain' => 'example.com']);
})->throws(ProvisioningException::class, 'cannot write provisioner snippets');

it('refuses a node with no storage for snippets', function () {
    Http::fake();
    $this->storage->update(['pve_content' => 'images,import']);

    app(ProvisioningService::class)->prepare($this->deployment->fresh(), ['domain' => 'example.com']);
})->throws(ProvisioningException::class, 'no storage with the Snippets content type');

it('cancels and cleans up an earlier run when the server is rebuilt', function () {
    fakeNode();
    $service = app(ProvisioningService::class);
    $old = $service->prepare($this->deployment, ['domain' => 'example.com']);

    $rebuild = $this->deployment->replicate()->fill(['requested_at' => now()]);
    $rebuild->save();
    $service->prepare($rebuild, ['domain' => 'example.com']);

    expect($old->fresh()->status)->toBe(ProvisioningStatus::CANCELLED);
    Queue::assertPushed(CleanUpProvisioningRunJob::class, fn ($job) => $job->runId === $old->id);
});

it('adds the step after configuring and before the first start', function () {
    $jobs = app(BuildServerAction::class)->getJobs($this->deployment, 'hunter22', ['domain' => 'example.com']);

    expect($this->deployment->steps()->pluck('name')->all())->toBe([
        'fetch-image', 'import', 'configure', 'update-password', 'prepare-provisioners', 'start-vm',
    ]);

    $job = collect($jobs)->first(fn ($job) => $job instanceof PrepareProvisionersJob);
    expect($job->fields)->toBe(['domain' => 'example.com']);
});

it('adds no step to an image without provisioners', function () {
    $this->image->provisioners()->detach();

    app(BuildServerAction::class)->getJobs($this->deployment, null);

    expect($this->deployment->steps()->pluck('name')->all())->not->toContain('prepare-provisioners');
});

it('adds no step to an import, which has no first boot', function () {
    $this->deployment->update(['type' => DeploymentType::IMPORT]);

    app(BuildServerAction::class)->getJobs($this->deployment, null);

    expect($this->deployment->steps()->pluck('name')->all())->not->toContain('prepare-provisioners');
});

function pendingRun(array $attributes = []): array
{
    $token = Str::random(64);

    $run = ProvisioningRun::create(array_merge([
        'server_id' => test()->server->id,
        'deployment_id' => test()->deployment->id,
        'node_id' => test()->node->id,
        'storage' => 'local',
        'token_hash' => ProvisioningRun::hashToken($token),
        'status' => ProvisioningStatus::PENDING,
        'provisioners' => [
            ['uuid' => test()->web->uuid, 'name' => 'Web'],
            ['uuid' => test()->agent->uuid, 'name' => 'Agent'],
        ],
    ], $attributes));

    return [$run, $token];
}

it('records a report and cleans up', function () {
    [$run, $token] = pendingRun();

    $this->withToken($token)
        ->postJson('/api/provisioning/report', ['results' => [
            ['provisioner' => $this->web->uuid, 'exit_code' => 0],
            ['provisioner' => $this->agent->uuid, 'exit_code' => 0],
        ]])
        ->assertNoContent();

    $run->refresh();
    expect($run->status)->toBe(ProvisioningStatus::SUCCEEDED)
        ->and($run->reported_at)->not->toBeNull()
        // toEqual: jsonb does not keep key order.
        ->and($run->results)->toEqual([
            ['provisioner' => $this->web->uuid, 'exit_code' => 0],
            ['provisioner' => $this->agent->uuid, 'exit_code' => 0],
        ]);

    Queue::assertPushed(CleanUpProvisioningRunJob::class, fn ($job) => $job->runId === $run->id);
});

it('fails a run when a script exits non-zero or never ran', function (array $exitCodes) {
    [$run, $token] = pendingRun();

    // The runner stops at the first failure, so a short list is a script
    // that never ran.
    $results = collect([$this->web, $this->agent])
        ->take(count($exitCodes))
        ->values()
        ->map(fn (Provisioner $provisioner, int $i) => ['provisioner' => $provisioner->uuid, 'exit_code' => $exitCodes[$i]])
        ->all();

    $this->withToken($token)->postJson('/api/provisioning/report', ['results' => $results])->assertNoContent();

    expect($run->fresh()->status)->toBe(ProvisioningStatus::FAILED);
})->with([
    'second failed' => [[0, 2]],
    'first failed, second skipped' => [[127]],
]);

it('refuses a report without the run token', function () {
    pendingRun();

    $this->postJson('/api/provisioning/report', ['results' => []])->assertUnauthorized();
    $this->withToken('not-the-token')->postJson('/api/provisioning/report', ['results' => []])->assertUnauthorized();
});

it('refuses a second report', function () {
    [$run, $token] = pendingRun(['status' => ProvisioningStatus::SUCCEEDED]);

    $this->withToken($token)->postJson('/api/provisioning/report', ['results' => []])->assertConflict();
});

it('takes a late report after a timeout', function () {
    [$run, $token] = pendingRun(['status' => ProvisioningStatus::TIMED_OUT, 'cleaned_up_at' => now()]);

    $this->withToken($token)
        ->postJson('/api/provisioning/report', ['results' => [
            ['provisioner' => $this->web->uuid, 'exit_code' => 0],
            ['provisioner' => $this->agent->uuid, 'exit_code' => 0],
        ]])
        ->assertNoContent();

    expect($run->fresh()->status)->toBe(ProvisioningStatus::SUCCEEDED);
    Queue::assertNotPushed(CleanUpProvisioningRunJob::class);
});

it('refuses results for a script the run does not have', function () {
    [, $token] = pendingRun();

    $this->withToken($token)
        ->postJson('/api/provisioning/report', ['results' => [['provisioner' => (string) Str::uuid(), 'exit_code' => 0]]])
        ->assertUnprocessable();
});

it('removes cicustom before deleting the snippet', function () {
    [$run] = pendingRun(['status' => ProvisioningStatus::SUCCEEDED]);
    $this->guestConfig = ['cicustom' => "vendor=local:snippets/{$run->snippet}"];
    fakeNode();

    app(ProvisioningService::class)->cleanUp($run);

    $sent = Http::recorded()->map(fn ($pair) => $pair[0])
        ->reject(fn (Request $r) => $r->method() === 'GET')
        ->values();

    expect($sent)->toHaveCount(2)
        ->and($sent[0]->data())->toBe(['delete' => 'cicustom'])
        ->and($sent[1]->method())->toBe('DELETE')
        ->and($sent[1]->url())->toBe("https://anchor.example.com/api/v1/snippets/{$run->snippet}")
        ->and(bearerClaims($sent[1])['snippet'])->toBe(['action' => 'delete', 'storage' => 'local', 'name' => $run->snippet])
        ->and($run->fresh()->cleaned_up_at)->not->toBeNull();
});

it('leaves a newer run\'s cicustom alone', function () {
    [$run] = pendingRun(['status' => ProvisioningStatus::CANCELLED]);
    $this->guestConfig = ['cicustom' => 'vendor=local:snippets/convoy-provisioner-newer.yaml'];
    fakeNode();

    app(ProvisioningService::class)->cleanUp($run);

    $sent = Http::recorded()->map(fn ($pair) => $pair[0])->reject(fn (Request $r) => $r->method() === 'GET');

    expect($sent)->toHaveCount(1)
        ->and($sent->first()->method())->toBe('DELETE');
});

it('still deletes the snippet of a server that is gone', function () {
    [$run] = pendingRun(['server_id' => null, 'status' => ProvisioningStatus::CANCELLED]);
    fakeNode();

    app(ProvisioningService::class)->cleanUp($run);

    expect(Http::recorded())->toHaveCount(1)
        ->and($run->fresh()->cleaned_up_at)->not->toBeNull();
});

it('drops a restored cicustom whose snippet is gone', function () {
    [$run] = pendingRun(['status' => ProvisioningStatus::SUCCEEDED, 'cleaned_up_at' => now()]);
    $this->guestConfig = ['cicustom' => "vendor=local:snippets/{$run->snippet}"];
    fakeNode();

    app(ProvisioningService::class)->dropStaleCicustom($this->server);

    $sent = Http::recorded()->map(fn ($pair) => $pair[0])->reject(fn (Request $r) => $r->method() === 'GET')->values();
    expect($sent)->toHaveCount(1)
        ->and($sent[0]->data())->toBe(['delete' => 'cicustom']);
});

it('keeps a restored cicustom whose run is still waiting', function () {
    [$run] = pendingRun();
    $this->guestConfig = ['cicustom' => "vendor=local:snippets/{$run->snippet}"];
    fakeNode();

    app(ProvisioningService::class)->dropStaleCicustom($this->server);

    expect(Http::recorded()->map(fn ($pair) => $pair[0])->reject(fn (Request $r) => $r->method() === 'GET'))->toBeEmpty();
});

it('times out runs past their deadline and cancels runs whose server is gone', function () {
    [$expired] = pendingRun(['deadline_at' => now()->subMinute()]);
    [$waiting] = pendingRun(['deadline_at' => now()->addMinute(), 'deployment_id' => null]);
    [$unstarted] = pendingRun(['deadline_at' => null, 'deployment_id' => null]);
    [$orphan] = pendingRun(['server_id' => null, 'deployment_id' => null]);

    app(ProvisioningService::class)->sweep();

    expect($expired->fresh()->status)->toBe(ProvisioningStatus::TIMED_OUT)
        ->and($waiting->fresh()->status)->toBe(ProvisioningStatus::PENDING)
        ->and($unstarted->fresh()->status)->toBe(ProvisioningStatus::PENDING)
        ->and($orphan->fresh()->status)->toBe(ProvisioningStatus::CANCELLED);

    Queue::assertPushed(CleanUpProvisioningRunJob::class, 2);
});

it('retries a cleanup that did not finish', function () {
    [$stale] = pendingRun(['status' => ProvisioningStatus::SUCCEEDED]);
    ProvisioningRun::whereKey($stale->id)->update(['updated_at' => now()->subMinutes(11)]);
    [$fresh] = pendingRun(['status' => ProvisioningStatus::SUCCEEDED, 'deployment_id' => null]);

    app(ProvisioningService::class)->sweep();

    Queue::assertPushed(CleanUpProvisioningRunJob::class, fn ($job) => $job->runId === $stale->id);
    Queue::assertNotPushed(CleanUpProvisioningRunJob::class, fn ($job) => $job->runId === $fresh->id);
});

function reinstall(array $body = []): TestResponse
{
    return test()->actingAs(test()->owner)
        ->withSession(confirmedSession())
        ->postJson('/api/client/servers/'.test()->server->uuid.'/settings/reinstall', array_merge([
            'image_uuid' => test()->image->uuid,
            'account_password' => 'correct-horse-battery',
            'start_on_completion' => true,
        ], $body));
}

it('asks for the image\'s fields on reinstall and passes them to the build', function () {
    reinstall(['provisioner_fields' => ['domain' => 'example.com']])->assertNoContent();

    $deployment = $this->server->deployments()->latest('id')->first();
    expect($deployment->steps()->pluck('name')->all())->toContain('prepare-provisioners');
});

it('refuses a reinstall without a required field', function () {
    reinstall()
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['provisioner_fields.domain' => 'The Domain field is required.']);
});

it('refuses a field the image does not have', function () {
    reinstall(['provisioner_fields' => ['domain' => 'example.com', 'shell' => 'x']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provisioner_fields');
});

it('refuses a field value with a line break', function () {
    reinstall(['provisioner_fields' => ['domain' => "example.com\nCONVOY_HOSTNAME=evil"]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['provisioner_fields.domain' => 'The Domain field cannot contain line breaks.']);
});

it('refuses a reinstall onto a node that cannot run the scripts', function () {
    $this->node->forceFill(['agent_capabilities' => []])->save();

    reinstall(['provisioner_fields' => ['domain' => 'example.com']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image_uuid');
});

it('asks for the fields when an admin creates a server from the image', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/servers', [
            'node_id' => $this->node->id,
            'image_uuid' => $this->image->uuid,
            'should_create_vm' => true,
            'deferred_os_selection' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provisioner_fields.domain');
});

it('asks for no fields when the server waits for its owner to pick an OS', function () {
    $response = $this->actingAs(admin())
        ->postJson('/api/admin/servers', [
            'node_id' => $this->node->id,
            'image_uuid' => $this->image->uuid,
            'should_create_vm' => true,
            'deferred_os_selection' => true,
        ]);

    expect($response->json('errors'))->not->toHaveKey('provisioner_fields.domain');
});
