<?php

namespace App\Data\User;

use Illuminate\Http\Request;

/**
 * The square of the uploaded picture the user framed, in source pixels.
 *
 * Coordinates rather than a pre-cropped file: the browser sends what to keep
 * and the panel does the pixel work, so the stored avatar is cut once from the
 * original instead of being re-encoded twice, and a client that sends nothing
 * still gets the centred square {@see AvatarService::square()} falls back to.
 */
class AvatarCropData
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
        public readonly int $size,
    ) {}

    /** Null unless the request carried a complete crop. */
    public static function fromRequest(Request $request): ?self
    {
        if (! $request->filled(['crop_x', 'crop_y', 'crop_size'])) {
            return null;
        }

        return new self(
            x: $request->integer('crop_x'),
            y: $request->integer('crop_y'),
            size: $request->integer('crop_size'),
        );
    }
}
