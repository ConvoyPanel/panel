<?php

namespace App\Data\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

/**
 * What an action was performed on, described well enough for a global admin feed to render a row
 * without loading every related model itself.
 *
 * The type is the class basename lowercased ('server', 'node', 'user') rather than the stored FQCN:
 * the frontend has no business knowing the PHP namespace, and the short form is what a filter
 * dropdown wants.
 */
class AuditSubjectData extends Data
{
    public function __construct(
        public string $type,
        public ?int $id,
        /** A display name where the record still exists; null once it has been deleted. */
        public ?string $label,
    ) {}

    public static function fromModel(AuditLog $log): ?self
    {
        if ($log->subject_type === null) {
            return null;
        }

        $subject = $log->subject;

        return new self(
            type: Str::lower(class_basename($log->subject_type)),
            id: $log->subject_id,
            label: $subject?->getAttribute('name')
                ?? $subject?->getAttribute('short_code')
                ?? $subject?->getAttribute('display_name'),
        );
    }
}
