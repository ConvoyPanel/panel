<?php

namespace App\Data\Audit;

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

/**
 * One audit entry on the wire.
 *
 * There is no rendered description: the `event` key and the `properties` bag are what the frontend
 * turns into a sentence, so wording can change without touching stored data. See
 * docs/audit-log-plan.md.
 */
class AuditLogData extends Data
{
    public function __construct(
        public int $id,
        public AuditEvent $event,
        public ?string $batch,
        public AuditActorData $actor,
        public ?AuditSubjectData $subject,
        /** @var array<string, mixed> */
        public array $properties,
        public ?string $ip,
        public ?string $userAgent,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(AuditLog $model, ?Request $request = null): self
    {
        $request ??= request();

        /** @var User|null $viewer */
        $viewer = $request->user() instanceof User ? $request->user() : null;
        $viewerIsAdmin = (bool) $viewer?->root_admin;

        // An address is personal data about whoever acted. Admins investigating need it; everyone
        // else only ever sees their own.
        $canSeeAddress = $viewerIsAdmin
            || ($viewer !== null && $model->actor instanceof User && $model->actor->is($viewer));

        return new self(
            id: $model->id,
            event: $model->event,
            batch: $model->batch,
            actor: AuditActorData::forViewer($model, $viewer, $viewerIsAdmin),
            subject: AuditSubjectData::fromModel($model),
            properties: $model->properties->toArray(),
            ip: $canSeeAddress ? $model->ip : null,
            userAgent: $canSeeAddress ? $model->user_agent : null,
            createdAt: CarbonImmutable::parse($model->created_at),
        );
    }
}
