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

    $response = $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => avatarUpload(300, 300)]);

    // The URL the account actually hands the frontend, not one rebuilt here:
    // an accessor that prefixed the path twice still passed a test that did.
    $url = parse_url($response->json('data.avatarUrl'), PHP_URL_PATH);

    $this->actingAs($user)
        ->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp');

    auth()->logout();
    $this->get($url)->assertNotFound();
});

/**
 * A picture whose left half is red and right half is blue, so which half came
 * back proves which square was actually cut.
 */
function splitUpload(int $width = 800, int $height = 400): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);

    imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 220, 20, 20));
    imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 20, 20, 220));

    $path = tempnam(sys_get_temp_dir(), 'avatar').'.png';
    imagepng($image, $path);
    imagedestroy($image);

    return new UploadedFile($path, 'split.png', 'image/png', null, true);
}

/** A pixel of what was stored, sampled at `$across` of its width, as [r, g, b]. */
function pixelAt(User $user, float $across = 0.5): array
{
    $image = imagecreatefromstring(storedAvatar($user));
    $rgb = imagecolorat($image, (int) (imagesx($image) * $across), intdiv(imagesy($image), 2));
    imagedestroy($image);

    return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
}

it('cuts the square the user framed rather than the middle', function () {
    $user = User::factory()->create();

    // The blue half: 400px square starting at x=400 on an 800x400 source.
    $this->actingAs($user)
        ->post('/api/client/account/avatar', [
            'avatar' => splitUpload(),
            'crop_x' => 400,
            'crop_y' => 0,
            'crop_size' => 400,
        ])
        ->assertSuccessful();

    [$r, , $b] = pixelAt($user);

    expect($b)->toBeGreaterThan($r);
});

it('still centres the crop when none is given', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/client/account/avatar', ['avatar' => splitUpload()])
        ->assertSuccessful();

    // The centred 400px square of an 800px-wide split image straddles the seam,
    // so it keeps red on its left and blue on its right. Sampled away from the
    // boundary itself, which is a pixel either way once WebP has been through
    // it.
    [$leftRed, , $leftBlue] = pixelAt($user, 0.25);
    [$rightRed, , $rightBlue] = pixelAt($user, 0.75);

    expect($leftRed)->toBeGreaterThan($leftBlue)
        ->and($rightBlue)->toBeGreaterThan($rightRed);
});

it('pulls an out-of-bounds crop back inside the picture', function () {
    $user = User::factory()->create();

    // Far past the right edge, and taller than the source: clamped rather than
    // refused, since the browser measures a scaled copy and can disagree by a
    // pixel at the edge.
    $this->actingAs($user)
        ->post('/api/client/account/avatar', [
            'avatar' => splitUpload(),
            'crop_x' => 5000,
            'crop_y' => 5000,
            'crop_size' => 9000,
        ])
        ->assertSuccessful();

    [$width, $height] = getimagesizefromstring(storedAvatar($user));

    expect($width)->toBe(400)->and($height)->toBe(400);
});

it('refuses a half-specified crop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/avatar', [
            'avatar' => avatarUpload(),
            'crop_x' => 10,
        ])
        ->assertJsonValidationErrors(['crop_y', 'crop_size']);
});
