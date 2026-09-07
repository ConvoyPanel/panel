<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as Filesystem;

/**
 * A real image file on disk, since the whole point of the pipeline is that it
 * decodes what it was handed rather than trusting the upload's metadata.
 * `UploadedFile::fake()->image()` produces exactly that.
 */
function avatarUpload(int $width = 1200, int $height = 900, string $name = 'me.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, $width, $height);
}

function storedAvatar(User $user): string
{
    return Filesystem::disk(config('convoy.avatars.disk'))->get($user->refresh()->avatar_path);
}

beforeEach(function () {
    Filesystem::fake(config('convoy.avatars.disk'));
});

it('stores an uploaded picture and points the account at it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => avatarUpload()])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    $user->refresh();

    expect($user->avatar_path)->toStartWith("avatars/{$user->uuid}/")
        ->and($user->avatar_path)->toEndWith('.webp');

    Filesystem::disk(config('convoy.avatars.disk'))->assertExists($user->avatar_path);
});

it('downscales a large picture to a square within the size cap', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => avatarUpload(2400, 1600)])
        ->assertSuccessful();

    [$width, $height] = getimagesizefromstring(storedAvatar($user));

    expect($width)->toBe(512)
        ->and($height)->toBe(512);
});

it('re-encodes to webp whatever format it was given', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => avatarUpload(600, 600, 'me.png')])
        ->assertSuccessful();

    expect(getimagesizefromstring(storedAvatar($user))[2])->toBe(IMAGETYPE_WEBP);
});

it('leaves a picture smaller than the cap at its own size', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => avatarUpload(200, 300)])
        ->assertSuccessful();

    expect(getimagesizefromstring(storedAvatar($user))[0])->toBe(200);
});

it('deletes the previous file when the picture is replaced', function () {
    $user = User::factory()->create();
    $disk = Filesystem::disk(config('convoy.avatars.disk'));

    $this->actingAs($user)->post('/api/client/account/avatar', ['avatar' => avatarUpload(400, 400)]);
    $first = $user->refresh()->avatar_path;

    $this->actingAs($user)->post('/api/client/account/avatar', ['avatar' => avatarUpload(600, 600)]);
    $second = $user->refresh()->avatar_path;

    expect($second)->not->toBe($first);
    $disk->assertMissing($first);
    $disk->assertExists($second);
});

it('removes the picture and its file', function () {
    $user = User::factory()->create();
    $disk = Filesystem::disk(config('convoy.avatars.disk'));

    $this->actingAs($user)->post('/api/client/account/avatar', ['avatar' => avatarUpload()]);
    $path = $user->refresh()->avatar_path;

    $this->actingAs($user)
        ->deleteJson('/api/client/account/avatar')
        ->assertSuccessful()
        ->assertJsonPath('data.avatarUrl', null);

    expect($user->refresh()->avatar_path)->toBeNull();
    $disk->assertMissing($path);
});

it('refuses a file that is not an image', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/avatar', [
            'avatar' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])
        ->assertJsonValidationErrors('avatar');
});

it('serves a stored picture to a signed-in browser and nobody else', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/api/client/account/avatar', ['avatar' => avatarUpload(300, 300)]);
    $path = $user->refresh()->avatar_path;
    $url = "/{$path}";

    $this->actingAs($user)->get($url)->assertOk();

    auth()->logout();
    $this->get($url)->assertNotFound();
});
