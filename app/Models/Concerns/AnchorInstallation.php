<?php

namespace App\Models\Concerns;

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;
use App\Settings\AnchorSettings;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Support\Carbon;

/**
 * The half of a record that is an Anchor installation.
 *
 * Three tables carry one: a {@see Node} (whose agent columns are
 * `agent_`-prefixed, because a node has a Proxmox liveness of its own), a
 * {@see Relay}, and an {@see AnchorEnrollment} still
 * waiting to become one of the other two.
 *
 * The column names differ, so the shared logic reaches them through the small
 * accessors each model implements rather than by naming columns. That keeps
 * "is this thing usable" in exactly one place — three copies of a version
 * comparison is how two of them end up disagreeing.
 */
trait AnchorInstallation
{
    abstract public function anchorName(): string;

    abstract public function anchorUuid(): ?string;

    abstract public function anchorSecret(): ?string;

    abstract public function anchorEnrolledAt(): ?Carbon;

    abstract public function anchorLastSeenAt(): ?Carbon;

    abstract public function anchorProtocolMin(): ?int;

    abstract public function anchorProtocolMax(): ?int;

    /** How the panel reaches this installation. Null until someone establishes it. */
    abstract public function anchorPublicUrl(): ?string;

    abstract public function anchorPanelUrlOverride(): ?string;

    /** What this installation runs as. Fixed for a node and a relay; recorded for a claim. */
    abstract public function anchorMode(): AnchorMode;

    /**
     * Writes what an installation just told us about itself.
     *
     * Each model maps the payload onto its own columns, because a node's agent
     * columns are prefixed to keep them apart from its Proxmox liveness.
     *
     * @param  array<string, mixed>  $payload
     */
    abstract public function recordAnchorHeartbeat(array $payload): void;

    public function anchorCompatibility(): AnchorCompatibility
    {
        if ($this->anchorUuid() === null || $this->anchorEnrolledAt() === null) {
            return AnchorCompatibility::UNENROLLED;
        }

        $lastSeen = $this->anchorLastSeenAt();

        if ($lastSeen === null || $lastSeen->lt(now()->subMinutes(AnchorProtocol::STATUS_TTL_MINUTES))) {
            return AnchorCompatibility::OFFLINE;
        }

        $min = $this->anchorProtocolMin();
        $max = $this->anchorProtocolMax();

        if ($min === null || $max === null || $min > AnchorProtocol::VERSION || $max < AnchorProtocol::VERSION) {
            return AnchorCompatibility::INCOMPATIBLE;
        }

        return AnchorCompatibility::COMPATIBLE;
    }

    public function hasAnchor(): bool
    {
        return $this->anchorUuid() !== null;
    }

    /**
     * Where this installation should reach the panel.
     *
     * The reverse of the public URL: an installation may sit on a network where
     * the panel's canonical address does not resolve (a private tunnel, a split
     * DNS horizon), so it can be pointed at one that does. Cascades its own
     * override over the panel-wide default, because a fleet usually shares one
     * such address and only occasionally needs them to differ.
     */
    public function anchorPanelUrl(): string
    {
        $override = $this->anchorPanelUrlOverride();

        return $override
            ? rtrim($override, '/')
            : app(AnchorSettings::class)->resolvedPanelUrl();
    }

    /**
     * Null when nobody has established how the panel reaches this installation.
     *
     * Returned rather than asserted away: a console that declines with a
     * sentence is recoverable, and a TypeError inside token issuance is not.
     */
    public function anchorWebsocketUrl(): ?string
    {
        $public = $this->anchorPublicUrl();

        if ($public === null) {
            return null;
        }

        $url = rtrim($public, '/').'/api/v1/console';

        return preg_replace('/^http/i', 'ws', $url) ?? $url;
    }
}
