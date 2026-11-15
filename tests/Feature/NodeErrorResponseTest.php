<?php

use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Repository\Proxmox\NodeUnreachableException;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Convoy\Services\Servers\ServerBuildService;
use Illuminate\Support\Facades\Http;

// A production panel shows a non-HTTP exception as "Server Error". These
// check that a node that can't be reached, or answers with an error, says so
// instead -- with debug off, as a real panel runs.
beforeEach(fn () => config(['app.debug' => false]));

it('turns a refused connection into a NodeUnreachableException', function () {
    Http::fake(['*' => Http::failedConnection()]);
    [$_, $_, $_, $server] = createServerModel();

    expect(fn () => app(ServerBuildService::class)->vmExists($server))
        ->toThrow(NodeUnreachableException::class);
});

it('tells an admin deleting a server that the node is unreachable', function () {
    Http::fake(['*' => Http::failedConnection()]);
    [$_, $_, $_, $server] = createServerModel();

    test()->actingAs(User::factory()->create(['root_admin' => true]))
        ->deleteJson("/api/admin/servers/{$server->uuid}")
        ->assertStatus(502)
        ->assertJsonPath('message', "Couldn't connect to the node. It may be offline or unreachable from the panel; try again once it's back.");

    // Nothing happened: the server is not left half-deleted.
    expect($server->fresh()->status)->toBeNull();
});

it('keeps the node address out of what the browser sees', function () {
    Http::fake(['*' => Http::failedConnection()]);
    [$_, $_, $node, $server] = createServerModel();

    $body = test()->actingAs(User::factory()->create(['root_admin' => true]))
        ->deleteJson("/api/admin/servers/{$server->uuid}")
        ->getContent();

    expect($body)->not->toContain($node->fqdn)->not->toContain('cURL');
});

it('passes Proxmox\'s own error through to a client retrying an install', function () {
    Http::fake(['*' => Http::response(['data' => null, 'message' => 'proxy error'], 503)]);
    [$user, $_, $node, $server] = createServerModel();
    $server->update(['status' => Status::INSTALL_FAILED->value]);
    $group = TemplateGroup::create(['node_id' => $node->id, 'name' => 'Linux', 'hidden' => false]);
    $template = Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 9000,
        'hidden' => false,
    ]);

    test()->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall", [
        'template_uuid' => $template->uuid,
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'start_on_completion' => false,
    ])
        ->assertStatus(502)
        ->assertJsonPath('message', 'Proxmox returned 503: proxy error');

    expect($server->fresh()->status)->toBe(Status::INSTALL_FAILED->value);
});
