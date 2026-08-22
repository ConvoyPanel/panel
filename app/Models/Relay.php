<?php

namespace App\Models;

use App\Enums\Anchor\AnchorMode;
use App\Models\Concerns\AnchorInstallation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A shared public endpoint that routes console sessions to agents.
 *
 * The part of the old `anchors` table that genuinely is not a node: it has no
 * location, no Proxmox credentials and no capacity, and folding it into `nodes`
 * would have meant all of those going null on it and every placement query
 * growing a filter that one of them would eventually forget.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $public_url
 * @property string|null $panel_url_override
 * @property string $secret
 * @property int|null $enrollment_key_id
 * @property string|null $enrollment_token_hash
 * @property Carbon|null $enrollment_expires_at
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $last_seen_at
 * @property string|null $version
 * @property int|null $protocol_min
 * @property int|null $protocol_max
 * @property array<int, string>|null $capabilities
 * @property array<string, mixed>|null $reported_facts
 */
class Relay extends Model
{
    use AnchorInstallation, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['secret', 'enrollment_token_hash'];

    public static array $validationRules = [
        'uuid' => 'required|uuid',
        'name' => 'required|string|max:191',
        'public_url' => 'nullable|url:http,https|max:2048',
        'panel_url_override' => 'nullable|url:http,https|max:2048',
        'secret' => 'required|string|min:32',
        'enrollment_key_id' => 'nullable|integer|exists:anchor_enrollment_keys,id',
        'enrollment_token_hash' => 'nullable|string|size:64',
        'enrollment_expires_at' => 'nullable|date',
        'enrolled_at' => 'nullable|date',
        'last_seen_at' => 'nullable|date',
        'version' => 'nullable|string|max:191',
        'protocol_min' => 'nullable|integer|min:1',
        'protocol_max' => 'nullable|integer|min:1',
        'capabilities' => 'nullable|array',
        'reported_facts' => 'nullable|array',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'enrollment_expires_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'protocol_min' => 'integer',
            'protocol_max' => 'integer',
            'capabilities' => 'array',
            'reported_facts' => 'array',
        ];
    }

    /** @return HasMany<Node, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class, 'relay_id');
    }

    /** @return BelongsTo<AnchorEnrollmentKey, $this> */
    public function enrollmentKey(): BelongsTo
    {
        return $this->belongsTo(AnchorEnrollmentKey::class, 'enrollment_key_id');
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

    public function anchorPublicUrl(): ?string
    {
        return $this->public_url;
    }

    public function anchorPanelUrlOverride(): ?string
    {
        return $this->panel_url_override;
    }

    public function anchorMode(): AnchorMode
    {
        return AnchorMode::RELAY;
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
