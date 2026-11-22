<?php

namespace App\Data\Audit;

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\SystemActor;
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
        /** The API token the action came in over. Null for a browser session, or when hidden from the viewer. */
        public ?AuditTokenData $token,
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

        /** @var User|SystemActor|null $actor */
        $actor = $request->user();
        $viewer = $actor instanceof User ? $actor : null;
        // An application token reads the audit log as the panel itself, which is an admin view.
        $viewerIsAdmin = $actor instanceof SystemActor || (bool) $viewer?->isAdmin();

        return self::build($model, $viewer, $viewerIsAdmin);
    }

    /**
     * The entry as an administrator sees it, nothing masked. For places with no viewer to ask,
     * like a webhook delivery.
     */
    public static function forStaff(AuditLog $model): self
    {
        return self::build($model, null, true);
    }

    private static function build(AuditLog $model, ?User $viewer, bool $viewerIsAdmin): self
    {
        // The address, browser and token say where an action came from, which is personal data
        // about whoever acted (and, for an application token, the name of an internal
        // integration). Admins investigating need it; everyone else only ever sees their own.
        $canSeeOrigin = $viewerIsAdmin
            || ($viewer !== null && $model->actor instanceof User && $model->actor->is($viewer));

        return new self(
            id: $model->id,
            event: $model->event,
            batch: $model->batch,
            actor: AuditActorData::forViewer($model, $viewer, $viewerIsAdmin),
            token: $canSeeOrigin ? AuditTokenData::fromLog($model) : null,
            subject: AuditSubjectData::fromModel($model),
            properties: $model->properties->toArray(),
            ip: $canSeeOrigin ? $model->ip : null,
            userAgent: $canSeeOrigin ? $model->user_agent : null,
            createdAt: CarbonImmutable::parse($model->created_at),
        );
    }
}
