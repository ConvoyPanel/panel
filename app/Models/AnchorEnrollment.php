<?php

namespace App\Models;

use App\Enums\Anchor\AnchorMode;
use App\Models\Concerns\AnchorInstallation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A machine that has introduced itself and is waiting to be let in.
 *
 * It is not a node yet, and deliberately does not live in `nodes` as one: a
 * node needs a location, which is a decision nobody has made at this point, and
 * a row with a null `location_id` would turn up in every placement scan and
 * capacity sum as a host that cannot host anything.
 *
 * It holds a real identity and a real secret because the agent starts
 * heartbeating the moment it enrolls. Approval promotes the row -- carrying the
 * same uuid and secret -- into a {@see Node} or a {@see Relay}, so the config
 * already written to the machine's disk stays valid.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property AnchorMode $mode
 * @property string $secret
 * @property int|null $enrollment_key_id
 * @property array<string, mixed>|null $reported_facts
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $last_seen_at
 * @property string|null $version
 * @property int|null $protocol_min
 * @property int|null $protocol_max
 * @property array<int, string>|null $capabilities
 */
class AnchorEnrollment extends Model
{
    use AnchorInstallation, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['secret'];

    public static array $validationRules = [
        'uuid' => 'required|uuid',
        'name' => 'required|string|max:191',
        'mode' => 'required|string|in:agent,relay',
        'secret' => 'required|string|min:32',
        'enrollment_key_id' => 'nullable|integer|exists:anchor_enrollment_keys,id',
        'reported_facts' => 'nullable|array',
        'enrolled_at' => 'nullable|date',
        'last_seen_at' => 'nullable|date',
        'version' => 'nullable|string|max:191',
        'protocol_min' => 'nullable|integer|min:1',
        'protocol_max' => 'nullable|integer|min:1',
        'capabilities' => 'nullable|array',
    ];

    protected function casts(): array
    {
        return [
            'mode' => AnchorMode::class,
            'secret' => 'encrypted',
            'enrolled_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'protocol_min' => 'integer',
            'protocol_max' => 'integer',
            'capabilities' => 'array',
            'reported_facts' => 'array',
        ];
    }

    /** @return BelongsTo<AnchorEnrollmentKey, $this> */
    public function enrollmentKey(): BelongsTo
    {
        return $this->belongsTo(AnchorEnrollmentKey::class, 'enrollment_key_id');
    }

    /** Whatever the machine said about itself, or null if it said nothing. */
    public function reported(string $key): mixed
    {
        return $this->reported_facts[$key] ?? null;
    }

    public function anchorName(): string
    {
        return $this->name;
    }

    public function anchorUuid(): ?string
    {
        return $this->uuid;
    }

    public function anchorSecret(): ?string
    {
        return $this->secret;
    }

    public function anchorEnrolledAt(): ?Carbon
    {
        return $this->enrolled_at;
    }

    public function anchorLastSeenAt(): ?Carbon
    {
        return $this->last_seen_at;
    }

    public function anchorProtocolMin(): ?int
    {
        return $this->protocol_min;
    }

    public function anchorProtocolMax(): ?int
    {
        return $this->protocol_max;
    }

    /** Nobody has established one yet; that is what approval is for. */
    public function anchorPublicUrl(): ?string
    {
        return null;
    }

    public function anchorPanelUrlOverride(): ?string
    {
        return null;
    }

    public function anchorMode(): AnchorMode
    {
        return $this->mode;
    }

    /** @param array<string, mixed> $payload */
    public function recordAnchorHeartbeat(array $payload): void
    {
        $this->update([
            'last_seen_at' => now(),
            'version' => $payload['version'],
            'protocol_min' => $payload['protocol_min'],
            'protocol_max' => $payload['protocol_max'],
            'capabilities' => $payload['capabilities'],
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
