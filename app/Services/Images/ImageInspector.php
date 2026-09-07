<?php

namespace App\Services\Images;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads what the panel needs to know about a disk image from the image itself.
 *
 * The provisioned size matters more than the file size: an imported disk
 * arrives at its virtual size and `qm disk resize` only grows, so it is the
 * floor a plan has to clear. Asking an admin to type it invites the number to
 * be wrong, and a wrong floor is a build that fails at import.
 *
 * qcow2 states it in its header, sixteen bytes in from the start, so this needs
 * no `qemu-img` on the panel host -- which is just as well, since the panel is
 * not a hypervisor and should not need hypervisor tooling to accept a file.
 */
class ImageInspector
{
    /** `QFI\xfb` -- the qcow2 magic. */
    private const QCOW_MAGIC = "QFI\xfb";

    /** Header bytes: magic(4) version(4) backing_offset(8) backing_size(4) cluster_bits(4) size(8). */
    private const HEADER_BYTES = 32;

    private const VIRTUAL_SIZE_OFFSET = 24;

    /**
     * The provisioned size a header declares, or null if this is not a qcow2.
     */
    public function virtualSizeFromHeader(string $header): ?int
    {
        if (strlen($header) < self::HEADER_BYTES || ! str_starts_with($header, self::QCOW_MAGIC)) {
            return null;
        }

        $unpacked = unpack('J', substr($header, self::VIRTUAL_SIZE_OFFSET, 8));

        // A zero would be a valid unsigned integer and a nonsense disk, so it
        // is reported as "unknown" rather than accepted as a floor of nothing.
        return ($unpacked[1] ?? 0) > 0 ? (int) $unpacked[1] : null;
    }

    public function virtualSizeOfFile(string $path): ?int
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            return $this->virtualSizeFromHeader((string) fread($handle, self::HEADER_BYTES));
        } finally {
            fclose($handle);
        }
    }

    /**
     * The same read, over HTTP, for an image the operator hosts themselves.
     *
     * One ranged request for the first 32 bytes: an origin that honours it
     * costs nothing, and one that does not simply leaves the size unknown for
     * the admin to supply. Downloading gigabytes to read a header would not be
     * a reasonable trade either way.
     */
    public function virtualSizeOfUrl(string $url): ?int
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Range' => 'bytes=0-'.(self::HEADER_BYTES - 1)])
                ->get($url);
        } catch (\Throwable $exception) {
            Log::debug('Could not read a disk image header over HTTP.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->virtualSizeFromHeader($response->body());
    }

    public function sha256OfFile(string $path): string
    {
        return (string) hash_file('sha256', $path);
    }
}
