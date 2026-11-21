<?php

use App\Models\User;

/**
 * The generated API reference at /api/docs. One generation per test is a few seconds, so this
 * checks the things that would make the reference useless rather than every endpoint.
 */
it('describes the application api', function () {
    $spec = $this->actingAs(User::factory()->create())
        ->getJson('/api/docs/application.json')
        ->assertOk()
        ->json();

    expect($spec['paths'])->toHaveKeys([
        '/servers',
        '/servers/{server}/backups',
        '/servers/{server}/settings/reinstall',
        '/webhooks',
    ])
        ->and($spec['components']['securitySchemes'])->toHaveKey('http');

    // Data classes are described field by field, not as an empty object.
    $server = $spec['components']['schemas']['ServerData'];
    expect($server['properties'])->toHaveKeys(['uuid', 'externalId', 'lifecycle'])
        ->and($server['properties']['lifecycle'])->toHaveKey('$ref');

    // Lists carry their item type.
    $list = $spec['paths']['/servers']['get']['responses']['200']['content']['application/json']['schema'];
    expect($list['properties']['items']['items']['$ref'])->toBe('#/components/schemas/ServerData');

    // The body of every write is read from its form request, including the ones whose rules
    // need the bound model.
    expect($spec['paths']['/users/{user}']['put'])->toHaveKey('requestBody')
        ->and($spec['paths']['/servers/{server}/settings/build']['patch'])->toHaveKey('requestBody');
});

it('describes the client api separately', function () {
    $spec = $this->actingAs(User::factory()->create())
        ->getJson('/api/docs/client.json')
        ->assertOk()
        ->json();

    expect(array_keys($spec['paths']))->toContain('/servers/{server}/backups')
        ->not->toContain('/webhooks');
});

it('keeps the reference to signed-in accounts unless made public', function () {
    $this->getJson('/api/docs/application.json')->assertForbidden();

    config(['convoy.api.docs_public' => true]);

    $this->getJson('/api/docs/client.json')->assertOk();
});
