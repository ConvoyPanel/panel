<?php

namespace App\Models;

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Settings\AnchorSettings;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property AnchorMode $mode
 * @property string $public_url
 * @property string|null $panel_url_override
 * @property string $secret
 * @property int|null $relay_id
 * @property Carbon|null $enrollment_expires_at
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $last_seen_at
 * @property string|null $version
 * @property int|null $protocol_min
 * @property int|null $protocol_max
 * @property array<int, string>|null $capabilities
 * @property Anchor|null $relay
 */
class Anchor extends Model
{
    public const PROTOCOL_VERSION = 1;

    public const STATUS_TTL_MINUTES = 5;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['secret', 'enrollment_token_hash'];

    public static array $validationRules = [
        'uuid' => 'required|uuid|unique:anchors,uuid',
        'name' => 'required|string|max:191',
        'mode' => 'required|string|in:agent,relay',
        'public_url' => 'required|url:http,https|max:2048',
        'panel_url_override' => 'nullable|url:http,https|max:2048',
        'secret' => 'required|string|min:32',
        'relay_id' => 'nullable|integer|exists:anchors,id',
        'enrollment_token_hash' => 'nullable|string|size:64',
        'enrollment_expires_at' => 'nullable|date',
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
            'enrollment_expires_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'protocol_min' => 'integer',
            'protocol_max' => 'integer',
            'capabilities' => 'array',
        ];
    }

    /** @return BelongsTo<Anchor, $this> */
    public function relay(): BelongsTo
    {
        return $this->belongsTo(self::class, 'relay_id');
    }

    /** @return HasMany<Anchor, $this> */
    public function agents(): HasMany
    {
        return $this->hasMany(self::class, 'relay_id');
    }

    /** @return HasMany<Node, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Where this anchor should reach the panel.
     *
     * The reverse of `public_url`: an anchor may sit on a network where the
     * panel's canonical address does not resolve (a private tunnel, a split
     * DNS horizon), so it can be pointed at one that does.
     *
     * Cascades this anchor's override over the panel-wide default, because a
     * fleet usually shares one such address and only occasionally needs them to
     * differ per anchor.
     */
    public function panelUrl(): string
    {
        $url = $this->panel_url_override
            ?: app(AnchorSettings::class)->panel_url
            ?: config('app.url');

        return rtrim($url, '/');
    }

    public function consoleWebsocketUrl(): string
    {
        $url = rtrim($this->public_url, '/').'/api/v1/console';

        return preg_replace('/^http/i', 'ws', $url) ?? $url;
    }

    public function compatibility(): AnchorCompatibility
    {
        if ($this->enrolled_at === null) {
            return AnchorCompatibility::UNENROLLED;
        }

        if ($this->last_seen_at === null || $this->last_seen_at->lt(now()->subMinutes(self::STATUS_TTL_MINUTES))) {
            return AnchorCompatibility::OFFLINE;
        }

        if (
            $this->protocol_min === null
            || $this->protocol_max === null
            || $this->protocol_min > self::PROTOCOL_VERSION
            || $this->protocol_max < self::PROTOCOL_VERSION
        ) {
            return AnchorCompatibility::INCOMPATIBLE;
        }

        return AnchorCompatibility::COMPATIBLE;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
