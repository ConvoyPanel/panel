<?php

namespace App\Data\Audit;

use App\Enums\Audit\AuditActorType;
use App\Models\AuditLog;
use App\Models\SystemActor;
use App\Models\User;
use App\Settings\AuditSettings;
use Spatie\LaravelData\Data;

/**
 * Who performed an action, resolved for one particular viewer.
 *
 * The label comes from the denormalised `actor_label` rather than the morph, so an action stays
 * attributable after the account that performed it is deleted.
 */
class AuditActorData extends Data
{
    public function __construct(
        public AuditActorType $type,
        public string $label,
        /** Null when the actor is masked, unattributed, or no longer exists. */
        public ?int $id,
    ) {}

    /**
     * @param  bool  $viewerIsAdmin  admin views are never masked
     */
    public static function forViewer(AuditLog $log, ?User $viewer, bool $viewerIsAdmin): self
    {
        if ($log->actor_type === null) {
            return new self(AuditActorType::UNKNOWN, 'Unknown', null);
        }

        if ($log->actor instanceof SystemActor || $log->actor_type === SystemActor::class) {
            return new self(AuditActorType::SYSTEM, 'System', $log->actor_id);
        }

        $label = $log->actor_label ?? 'Unknown';
        $actor = $log->actor;

        // Masking applies only to *other people's* admin actions seen by a non-admin. Viewers
        // always see their own name, and admins always see the truth.
        $isOtherAdmin = $actor instanceof User
            && $actor->root_admin
            && ! $actor->is($viewer);

        if ($isOtherAdmin && ! $viewerIsAdmin && ! app(AuditSettings::class)->reveal_staff_identity) {
            return new self(AuditActorType::STAFF, 'Staff', null);
        }

        return new self(AuditActorType::USER, $label, $log->actor_id);
    }
}
