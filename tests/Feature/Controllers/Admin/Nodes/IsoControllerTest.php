<?php

use Convoy\Jobs\Node\MonitorIsoDownloadJob;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'root_admin' => true,
    ]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
});

it('can fetch ISOs', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/isos",
    );

    $response->assertOk();
});

it('can create an ISO', function () {
    Http::fake([
        '*/download-url' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/Storage/DownloadIsoData.json'),
            ),
            200,
        ),
        '*/query-url-metadata*' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/Storage/QueryIsoData.json'),
            ),
            200,
        ),
    ]);

    $response = $this->actingAs($this->user)->postJson(
        "/api/admin/nodes/{$this->node->id}/isos",
        [
            'name' => 'Test ISO',
            'file_name' => 'test.iso',
            'link' => 'https://example.com/test.iso',
            'should_download' => true,
            'hidden' => false,
        ],
    );

    Queue::assertPushed(MonitorIsoDownloadJob::class);
    $response->assertOk();
});

it("can't create an ISO with file_name taken", function () {
    ISO::factory()->for($this->node)->create([
        'file_name' => 'duplicate.iso',
    ]);

    $response = $this->actingAs($this->user)->postJson(
        "/api/admin/nodes/{$this->node->id}/isos",
        [
            'name' => 'Test ISO',
            'file_name' => 'duplicate.iso',
            'link' => 'https://example.com/duplicate.iso',
            'should_download' => true,
            'hidden' => false,
        ],
    );

    $response->assertStatus(422);
});

it('can update an ISO', function () {
    $iso = ISO::factory()->for($this->node)->create();

    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}/isos/{$iso->uuid}",
        [
            'name' => 'Updated ISO',
        ],
    );

    $response->assertOk();
});

it('can delete an ISO', function () {
    Http::fake([
        '*/storage/*/content/*' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/Storage/DeleteIsoData.json'),
            ), 200,
        ),
    ]);
    $iso = ISO::factory()->for($this->node)->create();

    $response = $this->actingAs($this->user)->deleteJson(
        "/api/admin/nodes/{$this->node->id}/isos/{$iso->uuid}",
    );

    $response->assertNoContent();
});

it('can query link', function () {
    Http::fake([
        '*/query-url-metadata*' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/Storage/QueryIsoData.json'),
            ),
            200,
        ),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        '/tools/query-remote-file?link='.urlencode(
            'https://fedorapeople.org/groups/virt/virtio-win/direct-downloads/stable-virtio/virtio-win.iso',
        ),
    );

    $response->assertOk();
});
