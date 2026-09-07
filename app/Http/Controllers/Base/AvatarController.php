<?php

namespace App\Http\Controllers\Base;

use App\Services\Users\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as Filesystem;

/**
 * Serves a stored avatar to a signed-in browser.
 *
 * The panel is not a public site, so these do not sit on a public disk behind a
 * `storage:link` an operator has to remember to run -- they are read back
 * through the app, which also means swapping the avatars disk for S3 needs no
 * change here. The route only matches a UUID directory and a 64-character hash,
 * so there is no user-supplied path to traverse with.
 *
 * The file is named after its own bytes, so a new picture is a new URL and this
 * response can be cached for as long as the browser cares to keep it.
 */
class AvatarController
{
    public function __construct(
        private AvatarService $avatars,
    ) {}

    public function __invoke(Request $request, string $path)
    {
        // Checked here rather than with the `auth` middleware, which answers a
        // guest by redirecting to a `login` route the SPA does not register.
        // 404 either way: an <img> wants a failed image, not a login page, and
        // a signed-out scanner learns nothing about which accounts have one.
        abort_unless($request->user(), 404);

        $disk = Filesystem::disk($this->avatars->diskName());
        $file = "avatars/{$path}";

        abort_unless($disk->exists($file), 404);

        return $disk->response($file, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
