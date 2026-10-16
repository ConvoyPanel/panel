<?php

namespace App\Http\Controllers\Admin\Images;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\ImageUpload;
use App\Services\Images\ChunkedUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Takes a disk image an operator has no other way to host, a chunk at a time.
 *
 * One request is the wrong shape for this file. A Windows image is ten
 * gigabytes; Cloudflare rejects a body over 100 MB on most plans, PHP has its
 * own ceilings, and a connection that drops at 90% of a single POST costs the
 * entire transfer. So the upload is opened, appended to, and finished -- and
 * the offset the server reports is what makes a dropped connection cost one
 * chunk instead of everything.
 *
 * The upload is still not the image record: it is one disk of one version. It
 * comes back described rather than merely stored, because the two facts the
 * version form needs -- the hash, and the provisioned size that becomes the
 * plan floor -- are both properties of the file and neither should be typed.
 */
class ImageUploadController
{
    public function __construct(private ChunkedUploadService $uploads) {}

    /**
     * Open an upload and say how to feed it.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            // Optional, and only ever used to reject: the panel computes the
            // hash it reports either way, so a client that offers one is asking
            // to be told when the file it sent is not the file it meant to.
            'sha256' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/i'],
        ]);

        $upload = $this->uploads->begin(
            $validated['file_name'],
            (int) $validated['size'],
            $validated['sha256'] ?? null,
            $request->user(),
        );

        return response()->json($this->state($upload), Response::HTTP_CREATED);
    }

    /**
     * Where to resume from. The server's offset is the authoritative one.
     */
    public function show(Request $request, ImageUpload $imageUpload)
    {
        $this->authorizeUpload($request, $imageUpload);

        return $this->state($imageUpload);
    }

    /**
     * Append one chunk at `Upload-Offset`.
     *
     * The body is read as a stream rather than a form field: a multipart parse
     * would buffer the chunk through PHP's upload handling and reimpose the
     * very limits this endpoint exists to get out from under.
     */
    public function append(Request $request, ImageUpload $imageUpload)
    {
        $this->authorizeUpload($request, $imageUpload);

        $offset = $request->header('Upload-Offset');

        if (! is_numeric($offset)) {
            throw new ConflictHttpException('Send `Upload-Offset` with the byte this chunk starts at.');
        }

        $length = $request->header('Content-Length');

        $this->uploads->append(
            $imageUpload,
            (int) $offset,
            $request->getContent(true),
            is_numeric($length) ? (int) $length : null,
        );

        return $this->state($imageUpload);
    }

    /**
     * Hash what arrived, store it, and describe it.
     */
    public function finalize(Request $request, ImageUpload $imageUpload)
    {
        $this->authorizeUpload($request, $imageUpload);

        $described = $this->uploads->finish($imageUpload);

        Audit::record(
            AuditEvent::ADMIN_IMAGE_UPLOADED,
            properties: ['sha256' => $described['sha256'], 'size' => $described['size']],
        );

        return $described;
    }

    /**
     * Give up on an upload and reclaim its bytes.
     */
    public function destroy(Request $request, ImageUpload $imageUpload): Response
    {
        $this->authorizeUpload($request, $imageUpload);

        $this->uploads->discard($imageUpload);

        return response()->noContent();
    }

    /**
     * An upload is resumable by the account that opened it and nobody else.
     *
     * Every one of these routes is already admin-only; this is the narrower
     * rule that two admins uploading at once cannot append to each other's
     * file, which would corrupt both without either being told.
     */
    private function authorizeUpload(Request $request, ImageUpload $upload): void
    {
        if (filled($upload->user_id) && $upload->user_id !== $request->user()?->id) {
            throw new AccessDeniedHttpException('This upload belongs to someone else.');
        }
    }

    /**
     * @return array{uuid: string, offset: int, size: int, chunk_size: int, file_name: string, format: string}
     */
    private function state(ImageUpload $upload): array
    {
        return [
            'uuid' => $upload->uuid,
            'offset' => (int) $upload->received_bytes,
            'size' => (int) $upload->expected_size,
            'chunk_size' => $this->uploads->chunkBytes(),
            'file_name' => $upload->file_name,
            'format' => $upload->format,
        ];
    }
}
