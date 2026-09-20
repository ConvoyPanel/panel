<?php

use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * A qcow2 whose header declares `$virtualSize`, padded out to `$bytes`.
 *
 * The header is the point: the panel reads the provisioned size out of it
 * rather than asking anyone to type the number that becomes the plan floor.
 */
function fakeQcow(int $bytes = 4096, int $virtualSize = 5368709120): string
{
    $header = "QFI\xfb".pack('N', 3).str_repeat("\0", 16).pack('J', $virtualSize);

    return $header.str_repeat('a', max($bytes - strlen($header), 0));
}

/** Open an upload and return the state the server answered with. */
function beginUpload(User $admin, string $body, string $name = 'disk.qcow2', ?string $sha256 = null): array
{
    return test()->actingAs($admin)
        ->postJson('/api/admin/images/uploads', [
            'file_name' => $name,
            'size' => strlen($body),
            'sha256' => $sha256,
        ])
        ->assertCreated()
        ->json();
}

/** Send one chunk at `$offset`. */
function sendChunk(User $admin, string $uuid, string $chunk, int $offset)
{
    return test()->actingAs($admin)->call(
        'PUT',
        "/api/admin/images/uploads/{$uuid}",
        [],
        [],
        [],
        [
            'HTTP_UPLOAD_OFFSET' => (string) $offset,
            'CONTENT_LENGTH' => (string) strlen($chunk),
            'HTTP_ACCEPT' => 'application/json',
        ],
        $chunk,
    );
}

/** Open, send in `$chunkSize` pieces, and finish. */
function uploadWholeFile(User $admin, string $body, int $chunkSize = 1024, string $name = 'disk.qcow2'): array
{
    $state = beginUpload($admin, $body, $name);

    foreach (str_split($body, $chunkSize) as $index => $chunk) {
        sendChunk($admin, $state['uuid'], $chunk, $index * $chunkSize)->assertOk();
    }

    return test()->actingAs($admin)
        ->postJson("/api/admin/images/uploads/{$state['uuid']}/finalize")
        ->assertOk()
        ->json();
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('artifacts');
});

it('describes the assembled file rather than merely storing it', function () {
    $body = fakeQcow(4096, 5 * 1024 ** 3);

    $described = uploadWholeFile(admin(), $body);

    expect($described)->toBe([
        'path' => 'image-'.hash('sha256', $body).'.qcow2',
        'sha256' => hash('sha256', $body),
        'size' => 4096,
        // Read out of the qcow2 header, which is the whole reason the panel
        // does not have to ask for it.
        'virtual_size' => 5 * 1024 ** 3,
        'format' => 'qcow2',
    ]);

    Storage::disk('artifacts')->assertExists($described['path']);

    expect(Storage::disk('artifacts')->get($described['path']))->toBe($body);
});

it('advertises a chunk size that fits through a proxy', function () {
    $state = beginUpload(admin(), fakeQcow());

    expect($state['offset'])->toBe(0)
        // Comfortably under Cloudflare's 100 MB body limit, which is the
        // constraint this endpoint exists for.
        ->and($state['chunk_size'])->toBeLessThan(100 * 1000 * 1000)
        ->and($state['chunk_size'])->toBeGreaterThan(0);
});

it('resumes from the offset the server reports', function () {
    $admin = admin();
    $body = fakeQcow(3000);
    $state = beginUpload($admin, $body);

    sendChunk($admin, $state['uuid'], substr($body, 0, 1000), 0)->assertOk();

    // What a client does after a dropped connection: ask where it got to.
    $this->actingAs($admin)
        ->getJson("/api/admin/images/uploads/{$state['uuid']}")
        ->assertOk()
        ->assertJsonPath('offset', 1000);

    sendChunk($admin, $state['uuid'], substr($body, 1000), 1000)->assertOk();

    $described = $this->actingAs($admin)
        ->postJson("/api/admin/images/uploads/{$state['uuid']}/finalize")
        ->assertOk()
        ->json();

    expect($described['sha256'])->toBe(hash('sha256', $body));
});

it('refuses a chunk sent at the wrong place', function () {
    $admin = admin();
    $body = fakeQcow(3000);
    $state = beginUpload($admin, $body);

    sendChunk($admin, $state['uuid'], substr($body, 0, 1000), 0)->assertOk();

    // A chunk accepted at the wrong offset would produce a file of exactly the
    // right length and entirely the wrong bytes.
    sendChunk($admin, $state['uuid'], substr($body, 2000), 2000)->assertStatus(409);

    $this->actingAs($admin)
        ->getJson("/api/admin/images/uploads/{$state['uuid']}")
        ->assertJsonPath('offset', 1000);
});

it('will not finish an upload that is still short', function () {
    $admin = admin();
    $body = fakeQcow(3000);
    $state = beginUpload($admin, $body);

    sendChunk($admin, $state['uuid'], substr($body, 0, 1000), 0)->assertOk();

    $this->actingAs($admin)
        ->postJson("/api/admin/images/uploads/{$state['uuid']}/finalize")
        ->assertStatus(409);

    expect(ImageUpload::count())->toBe(1);
});

it('rejects a file that is not what the client said it was', function () {
    $admin = admin();
    $body = fakeQcow(2048);
    $state = beginUpload($admin, $body, sha256: str_repeat('a', 64));

    sendChunk($admin, $state['uuid'], $body, 0)->assertOk();

    $this->actingAs($admin)
        ->postJson("/api/admin/images/uploads/{$state['uuid']}/finalize")
        ->assertStatus(422);

    // The bytes go with the rejection: a file the panel will not vouch for is
    // not a file it should keep.
    expect(ImageUpload::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('image-uploads');
});

it('refuses to open an upload for something that is not a disk image', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/images/uploads', [
            'file_name' => 'notes.txt',
            'size' => 10,
        ])
        ->assertStatus(422);
});

it('stores one file when the same image is uploaded twice', function () {
    $admin = admin();
    $body = fakeQcow(2048);

    $first = uploadWholeFile($admin, $body);
    $second = uploadWholeFile($admin, $body);

    // Named after the hash, like the copy that lands on a node.
    expect($second['path'])->toBe($first['path'])
        ->and(Storage::disk('artifacts')->files())->toHaveCount(1);
});

it('treats a raw image as its own provisioned size', function () {
    $body = str_repeat('z', 2048);

    $described = uploadWholeFile(admin(), $body, name: 'disk.img');

    expect($described['format'])->toBe('raw')
        ->and($described['virtual_size'])->toBe(2048)
        ->and($described['path'])->toBe('image-'.hash('sha256', $body).'.raw');
});

it('gives back the disk when an upload is cancelled', function () {
    $admin = admin();
    $body = fakeQcow(2048);
    $state = beginUpload($admin, $body);

    sendChunk($admin, $state['uuid'], substr($body, 0, 1024), 0)->assertOk();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/images/uploads/{$state['uuid']}")
        ->assertNoContent();

    expect(ImageUpload::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('image-uploads');
});

it('sweeps uploads nobody came back to finish', function () {
    $admin = admin();
    $state = beginUpload($admin, fakeQcow(2048));

    sendChunk($admin, $state['uuid'], 'partial', 0)->assertOk();

    ImageUpload::query()->update(['updated_at' => now()->subDays(3)]);

    $this->artisan('maintenance:prune-image-uploads')->assertSuccessful();

    expect(ImageUpload::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('image-uploads');
});

it('keeps one admin from appending to another admin’s upload', function () {
    $state = beginUpload(admin(), fakeQcow(2048));

    sendChunk(admin(), $state['uuid'], 'nope', 0)->assertForbidden();
});

it('is closed to anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/admin/images/uploads', ['file_name' => 'disk.qcow2', 'size' => 1])
        ->assertForbidden();
});
