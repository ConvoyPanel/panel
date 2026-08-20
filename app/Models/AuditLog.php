<?php

namespace App\Models;

use App\Enums\Audit\AuditEvent;
use App\Enums\Audit\AuditVisibility;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * One recorded action. Rows are append-only: there is deliberately no `updated_at`, because an
 * audit entry that can be edited is not an audit entry.
 *
 * The predecessor of this table (`activity_logs`, ported from Pterodactyl in 2022) was never wired
 * up and drifted out of sync with its own migrations. See docs/audit-log-plan.md.
 *
 * @property int $id
 * @property ?string $batch
 * @property AuditEvent $event
 * @property ?int $actor_id
 * @property ?string $actor_type
 * @property ?string $actor_label
 * @property ?int $api_token_id
 * @property ?int $subject_id
 * @property ?string $subject_type
 * @property ?string $ip
 * @property ?string $user_agent
 * @property Collection $properties
 * @property ?CarbonImmutable $created_at
 */
class AuditLog extends Model
{
    /** Append-only: Laravel manages `created_at`, and there is no `updated_at` column at all. */
    public const UPDATED_AT = null;

    protected bool $immutableDates = true;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'event' => AuditEvent::class,
            'properties' => 'collection',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * Who acted. A {@see User} for anything a person did, a {@see SystemActor} for panel-wide
     * application tokens, and null only when the action could not be attributed at all.
     *
     * The relation resolves to null once the actor is deleted — nothing in this panel soft-deletes
     * — so read {@see $actor_label} for display and treat this relation as "the actor, if they
     * still exist".
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /** What was acted on — a Server, Node, User, token, or whatever else the event concerns. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** The API token used, when the action arrived over the API rather than a browser session. */
    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'api_token_id');
    }

    public function scopeForEvent(Builder $builder, AuditEvent $event): Builder
    {
        return $builder->where('event', $event->value);
    }

    public function scopeForActor(Builder $builder, Model $actor): Builder
    {
        return $builder->whereMorphedTo('actor', $actor);
    }

    public function scopeForSubject(Builder $builder, Model $subject): Builder
    {
        return $builder->whereMorphedTo('subject', $subject);
    }

    /**
     * Restricts a query to the events a non-admin is allowed to see. Applied on top of whatever
     * subject scoping the caller has already done — this filters by event *kind*, not by ownership.
     */
    public function scopeClientVisible(Builder $builder): Builder
    {
        $hidden = array_map(
            fn (AuditEvent $event) => $event->value,
            array_values(array_filter(
                AuditEvent::cases(),
                fn (AuditEvent $event) => $event->visibility() === AuditVisibility::ADMIN_ONLY,
            )),
        );

        return $hidden === [] ? $builder : $builder->whereNotIn('event', $hidden);
    }
}
