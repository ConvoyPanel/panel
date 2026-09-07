<?php

namespace App\Services\Users;

use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as Filesystem;
use Illuminate\Validation\ValidationException;

/**
 * Turns whatever picture someone uploaded into the one shape the panel renders.
 *
 * A browser never receives the original. Every upload is decoded, cropped to a
 * square, scaled down to at most {@see self::SIZE} pixels and re-encoded as
 * WebP, so a 12MP phone photo reaches the sidebar as a ~20KB file and nothing
 * downstream has to think about EXIF, colour profiles or format support.
 *
 * Re-encoding is also what makes the upload safe to serve: the bytes written to
 * disk are produced by GD, not by the uploader, so a polyglot file that is both
 * a valid PNG and a valid script does not survive the round trip.
 */
class AvatarService
{
    /** The longest edge of a stored avatar. Twice the largest place it renders, for HiDPI. */
    public const SIZE = 512;

    /**
     * Refuse to decode anything past 50 megapixels. The dimension is what costs
     * memory (~4 bytes a pixel once GD has it), not the file size -- a 200KB PNG
     * can claim 30000x30000 and take the process down with it.
     */
    private const MAX_SOURCE_PIXELS = 50_000_000;

    /** What `imagewebp` is handed. High enough that a face survives, low enough to stay small. */
    private const QUALITY = 82;

    public function store(User $user, UploadedFile $upload): User
    {
        $source = $this->decode($upload);

        try {
            $canvas = $this->square($source);

            try {
                $bytes = $this->encode($canvas);
            } finally {
                imagedestroy($canvas);
            }
        } finally {
            imagedestroy($source);
        }

        // Namespaced by the account and named after the bytes: replacing an
        // avatar writes a new file rather than overwriting a cached one, so the
        // URL changes with the picture and can be cached forever.
        $path = "avatars/{$user->uuid}/".hash('sha256', $bytes).'.webp';

        $disk = Filesystem::disk($this->diskName());
        $disk->put($path, $bytes);

        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous && $previous !== $path) {
            $disk->delete($previous);
        }

        return $user;
    }

    public function remove(User $user): User
    {
        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => null])->save();

        if ($previous) {
            Filesystem::disk($this->diskName())->delete($previous);
        }

        return $user;
    }

    /**
     * Everything the account owns on the avatars disk.
     *
     * Deleting the account deletes the directory, not just the file the column
     * points at, so a write that raced a deletion cannot leave a picture behind.
     */
    public function purge(User $user): void
    {
        Filesystem::disk($this->diskName())->deleteDirectory("avatars/{$user->uuid}");
    }

    public function diskName(): string
    {
        return (string) config('convoy.avatars.disk', 'local');
    }

    /**
     * The upload as a GD image, or a validation error the form can render.
     *
     * `getimagesize` reads the header rather than trusting the filename or the
     * browser-supplied content type, so this is also the check that the file is
     * the kind of image it claims to be.
     */
    private function decode(UploadedFile $upload): GdImage
    {
        $path = $upload->getRealPath();
        $info = $path ? @getimagesize($path) : false;

        if ($info === false) {
            throw ValidationException::withMessages([
                'avatar' => 'That file is not an image the panel can read.',
            ]);
        }

        [$width, $height, $type] = $info;

        if ($width * $height > self::MAX_SOURCE_PIXELS) {
            throw ValidationException::withMessages([
                'avatar' => 'That image is too large to process. Use one under 50 megapixels.',
            ]);
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages([
                'avatar' => 'That image format is not supported. Use a JPEG, PNG, WebP or GIF.',
            ]);
        }

        return $type === IMAGETYPE_JPEG
            ? $this->orient($image, $path)
            : $image;
    }

    /**
     * Applies the EXIF orientation a phone camera writes instead of rotating the
     * pixels. Without this a portrait photo is stored on its side, because the
     * tag is metadata GD drops and the browser only honours it on the original.
     */
    private function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($path)['Orientation'] ?? null;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * The centred square of the source, scaled to at most {@see self::SIZE}.
     *
     * Cropping rather than letterboxing because every avatar in the panel is
     * rendered in a circle -- padding a wide photo would only put bars inside
     * the circle. Small pictures are left at their own size rather than blown
     * up to the full 512, which only invents blur.
     */
    private function square(GdImage $source): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $edge = min($width, $height);
        $size = min($edge, self::SIZE);

        $canvas = imagecreatetruecolor($size, $size);

        // WebP keeps alpha, and a PNG with a transparent background is a common
        // avatar. Without these two calls GD composites it onto black.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            intdiv($width - $edge, 2),
            intdiv($height - $edge, 2),
            $size,
            $size,
            $edge,
            $edge,
        );

        return $canvas;
    }

    private function encode(GdImage $image): string
    {
        ob_start();
        imagewebp($image, null, self::QUALITY);

        return (string) ob_get_clean();
    }
}
