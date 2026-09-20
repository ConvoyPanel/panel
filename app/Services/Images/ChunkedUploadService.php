<?php

namespace App\Services\Images;

use App\Models\ImageUpload;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage as Filesystem;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Assembles a disk image from the pieces it arrives in.
 *
 * Three constraints shape all of this and none of them are negotiable: the file
 * is measured in gigabytes, so it never exists in memory; the connection
 * carrying it will drop, so every byte already accepted has to still be there
 * afterwards; and the panel is the only thing that can say what the file
 * actually is, so the hash it reports is computed here rather than trusted from
 * whoever sent it.
 *
 * The result is an append-only partial file plus an offset in the database, and
 * the offset is authoritative. A client that lost track asks for it and carries
 * on from there, which is the whole of the resume protocol.
 */
class ChunkedUploadService
{
    /** Extensions a disk image may arrive under, and the format each means. */
    private const FORMATS = ['qcow2' => 'qcow2', 'img' => 'raw', 'raw' => 'raw'];

    public function __construct(
        private ImageInspector $inspector,
        private ImageSourceResolver $resolver,
    ) {}

    public function chunkBytes(): int
    {
        return (int) config('convoy.artifacts.upload_chunk_bytes', 16 * 1024 * 1024);
    }

    /**
     * The format a file name implies, or null if it is not a disk image.
     */
    public function formatFor(string $fileName): ?string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return self::FORMATS[$extension] ?? null;
    }

    public function begin(string $fileName, int $size, ?string $sha256, ?User $user): ImageUpload
    {
        $format = $this->formatFor($fileName) ?? throw new UnprocessableEntityHttpException(
            'A disk image must be a .qcow2, .img or .raw file.',
        );

        $upload = ImageUpload::create([
            'user_id' => $user?->id,
            'file_name' => basename($fileName),
            'format' => $format,
            'expected_size' => $size,
            'expected_sha256' => $sha256 ? strtolower($sha256) : null,
            'received_bytes' => 0,
        ]);

        // Created empty up front so `append` never has to decide between
        // "nothing has arrived yet" and "the file went missing".
        $this->local()->put($upload->partialPath(), '');

        return $upload;
    }

    /**
     * Append one chunk, and answer with the new offset.
     *
     * The offset the client claims has to match the one on record. A mismatch
     * is not an error to paper over -- accepting a chunk at the wrong place
     * would produce a file that is the right length and the wrong bytes, and
     * nothing downstream would notice until a node failed to boot it.
     *
     * @param  resource  $stream
     */
    public function append(ImageUpload $upload, int $offset, $stream, ?int $length): int
    {
        if ($offset !== $upload->received_bytes) {
            throw new ConflictHttpException(
                "This upload is at byte {$upload->received_bytes}. Resume from there.",
            );
        }

        if (filled($length) && $offset + $length > $upload->expected_size) {
            throw new UnprocessableEntityHttpException(
                'That chunk would take the upload past the size it was started with.',
            );
        }

        $path = $this->local()->path($upload->partialPath());
        $handle = fopen($path, 'ab');

        if ($handle === false) {
            throw new ConflictHttpException('The partial upload is no longer on disk. Start it again.');
        }

        try {
            // Copied stream to stream: a chunk is tens of megabytes and the
            // file it joins is gigabytes, and neither belongs in memory.
            $written = (int) stream_copy_to_stream($stream, $handle);
        } finally {
            fclose($handle);
        }

        // A dropped connection mid-chunk leaves a short write. Rolling it back
        // rather than recording it is what keeps the offset exact, and so what
        // makes resuming safe: the client re-sends one whole chunk, never a
        // sliver of one.
        if (filled($length) && $written !== $length) {
            $this->truncateTo($path, $offset);

            throw new ConflictHttpException(
                'That chunk arrived incomplete. Resume from byte '.$offset.'.',
            );
        }

        $upload->update(['received_bytes' => $offset + $written]);

        return $upload->received_bytes;
    }

    /**
     * Hash the assembled file, store it under its digest, and describe it.
     *
     * The response is the same shape the single-request upload returned, because
     * what the version form needs has not changed: where the file is, what it
     * hashes to, and the provisioned size that becomes the plan floor.
     *
     * @return array{path: string, sha256: string, size: int, virtual_size: int, format: string}
     */
    public function finish(ImageUpload $upload): array
    {
        $local = $this->local();
        $path = $local->path($upload->partialPath());
        $size = (int) @filesize($path);

        if ($size !== $upload->expected_size) {
            throw new ConflictHttpException(sprintf(
                'The upload is %d bytes and was started as %d. Resume it before finishing.',
                $size,
                $upload->expected_size,
            ));
        }

        // Streamed, not read: `hash_file` walks the file in blocks, so a 10 GB
        // image costs no more memory than a 10 MB one.
        $sha256 = $this->inspector->sha256OfFile($path);

        if (filled($upload->expected_sha256) && ! hash_equals($upload->expected_sha256, $sha256)) {
            $this->discard($upload);

            throw new UnprocessableEntityHttpException(
                'The assembled file does not match the checksum this upload was started with.',
            );
        }

        // Named after the hash, like the copy that lands on a node: uploading
        // the same image twice costs one file, and a re-upload after a failed
        // version cannot leave an orphan under a different name.
        $stored = "image-{$sha256}.{$upload->format}";
        $artifacts = Filesystem::disk($this->resolver->diskName());

        if (! $artifacts->exists($stored)) {
            $handle = fopen($path, 'rb');

            try {
                $artifacts->writeStream($stored, $handle);
            } finally {
                is_resource($handle) && fclose($handle);
            }
        }

        $virtualSize = $this->inspector->virtualSizeOfFile($path)
            // A raw image is its own virtual size; only qcow2 declares one.
            ?? $size;

        $this->discard($upload);

        return [
            'path' => $stored,
            'sha256' => $sha256,
            'size' => $size,
            'virtual_size' => $virtualSize,
            'format' => $upload->format,
        ];
    }

    public function discard(ImageUpload $upload): void
    {
        $this->local()->delete($upload->partialPath());

        $upload->delete();
    }

    /**
     * Sweep uploads nobody came back to finish.
     *
     * An abandoned upload is the expensive kind of leftover -- an unfinished
     * Windows image is several gigabytes of a disk that nothing references and
     * no screen lists.
     */
    public function pruneOlderThan(CarbonInterface $cutoff): int
    {
        return ImageUpload::where('updated_at', '<', $cutoff)
            ->get()
            ->each(fn (ImageUpload $upload) => $this->discard($upload))
            ->count();
    }

    private function truncateTo(string $path, int $offset): void
    {
        $handle = fopen($path, 'r+b');

        if ($handle === false) {
            return;
        }

        try {
            ftruncate($handle, $offset);
        } finally {
            fclose($handle);
        }
    }

    private function local()
    {
        return Filesystem::disk((string) config('convoy.artifacts.upload_disk', 'local'));
    }
}
