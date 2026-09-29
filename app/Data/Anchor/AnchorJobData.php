<?php

namespace App\Data\Anchor;

use App\Enums\Anchor\AnchorJobStatus;
use Spatie\LaravelData\Data;

/**
 * One job on an Anchor, as the agent reports it.
 *
 * Exports and installs share a record because the agent has one job registry
 * and one `Job` shape. The four artifact fields are populated only by an
 * export that has finished; an install leaves them null and an unfinished
 * export has nothing to put in them yet.
 */
class AnchorJobData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly AnchorJobStatus $status,
        /** Percent complete within the current phase, 0-100. */
        public readonly int $progress,
        /** Bytes moved so far, and the total when the agent knows one. */
        public readonly int $transferred,
        public readonly ?int $total,
        public readonly ?string $error,
        /** The opaque artifact id an export produced. */
        public readonly ?string $artifact,
        public readonly ?string $sha256,
        public readonly ?int $size,
        /** Where the archive landed on the node. Diagnostics only. */
        public readonly ?string $path,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromRaw(array $raw): self
    {
        return new self(
            id: (string) ($raw['id'] ?? ''),
            status: AnchorJobStatus::parse($raw['status'] ?? null),
            progress: (int) ($raw['progress'] ?? 0),
            // `downloaded` on an install, `exported` on an export: the agent
            // names the bytes after what it did with them. Either way the
            // panel wants one number.
            transferred: (int) ($raw['downloaded'] ?? $raw['exported'] ?? 0),
            total: isset($raw['total']) ? (int) $raw['total'] : null,
            error: isset($raw['error']) ? (string) $raw['error'] : null,
            artifact: isset($raw['artifact']) ? (string) $raw['artifact'] : null,
            sha256: isset($raw['sha256']) ? (string) $raw['sha256'] : null,
            size: isset($raw['size']) ? (int) $raw['size'] : null,
            path: isset($raw['path']) ? (string) $raw['path'] : null,
        );
    }

    /** What went wrong, in the agent's own words where it gave any. */
    public function reason(string $fallback): string
    {
        return $this->error !== null && $this->error !== '' ? $this->error : $fallback;
    }
}
