<?php

namespace App\Http\Controllers\Admin\Images;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Services\Images\ImageInspector;
use App\Services\Images\ImageSourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as Filesystem;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Takes a disk image an operator has no other way to host.
 *
 * The upload is not the image record -- it is one disk of one version. It comes
 * back described rather than merely stored, because the two facts the version
 * form needs (the hash, and the provisioned size that becomes the plan floor)
 * are both properties of the file and neither should be typed by hand.
 */
class ImageUploadController
{
    public function __construct(
        private ImageInspector $inspector,
        private ImageSourceResolver $resolver,
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $upload = $request->file('file');
        $extension = strtolower((string) $upload->getClientOriginalExtension());

        if (! in_array($extension, ['qcow2', 'img', 'raw'], true)) {
            throw new UnprocessableEntityHttpException(
                'A disk image must be a .qcow2, .img or .raw file.',
            );
        }

        $sha256 = $this->inspector->sha256OfFile($upload->getRealPath());

        // Named after the hash, like the copy that lands on a node: uploading
        // the same image twice costs one file, and a re-upload after a failed
        // version cannot leave an orphan under a different name.
        $format = $extension === 'qcow2' ? 'qcow2' : 'raw';
        $path = "image-{$sha256}.{$format}";

        $disk = Filesystem::disk($this->resolver->diskName());

        if (! $disk->exists($path)) {
            $disk->putFileAs('', $upload, $path);
        }

        $virtualSize = $this->inspector->virtualSizeOfFile($disk->path($path))
            // A raw image is its own virtual size; only qcow2 declares one.
            ?? $disk->size($path);

        Audit::record(
            AuditEvent::ADMIN_IMAGE_UPLOADED,
            properties: ['sha256' => $sha256, 'size' => $disk->size($path)],
        );

        return [
            'path' => $path,
            'sha256' => $sha256,
            'size' => $disk->size($path),
            'virtual_size' => $virtualSize,
            'format' => $format,
        ];
    }
}
