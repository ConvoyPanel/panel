<?php

namespace App\Services\Images;

use App\Data\Image\ImageDiskData;
use Illuminate\Support\Facades\Storage as Filesystem;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Turns a disk record into a URL a node can fetch.
 *
 * An operator gives the panel a disk one of two ways: a public URL they host,
 * or an upload the panel keeps on a filesystem disk. Both answer the same
 * question, so this is where the difference stops -- everything downstream
 * receives a URL and a hash and never learns which it was handed.
 *
 * Hosted files are served over a signed link that expires, so a node never
 * holds a credential for the panel's storage and a leaked URL is worthless
 * shortly after the download it was minted for.
 */
class ImageSourceResolver
{
    public function urlFor(ImageDiskData $disk): string
    {
        if (! $disk->isHosted()) {
            return $disk->url ?? throw new ConflictHttpException(
                'This image disk has neither a URL nor an uploaded file.',
            );
        }

        $disk_ = Filesystem::disk($this->diskName());

        // A local filesystem only signs URLs when the disk is served, which is
        // exactly what the shipped config turns on. Saying so beats handing
        // Proxmox a link it will fetch an HTML error page from.
        if (! method_exists($disk_, 'temporaryUrl')) {
            throw new ConflictHttpException(
                'The artifacts filesystem cannot produce download links. Set `serve` on the disk, or host the file yourself.',
            );
        }

        return $disk_->temporaryUrl(
            (string) $disk->path,
            now()->addMinutes((int) config('convoy.artifacts.url_ttl_minutes', 120)),
        );
    }

    /**
     * The content-addressed name this disk takes on a node.
     *
     * Named after the hash rather than the image, so two definitions built from
     * the same disk share one file, a rebuild never collides with the build it
     * replaces, and a node can answer "do I already have this?" by name alone.
     * The `.qcow2` suffix is not decoration: PVE refuses an import file whose
     * extension it does not recognise.
     */
    public function fileNameFor(ImageDiskData $disk): string
    {
        return "image-{$disk->sha256}.{$disk->format}";
    }

    public function diskName(): string
    {
        return (string) config('convoy.artifacts.disk', 'artifacts');
    }
}
