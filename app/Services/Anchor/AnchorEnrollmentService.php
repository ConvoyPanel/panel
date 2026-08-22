<?php

namespace App\Services\Anchor;

use App\Data\Anchor\AnchorEnrollmentData;
use App\Models\Node;
use App\Models\Relay;
use Illuminate\Support\Str;

/**
 * Re-keys an installation the panel already has a row for.
 *
 * The other half of enrollment, and deliberately a different credential from
 * {@see AnchorEnrollmentKeyService}: this one is bound to one record and can
 * only ever hand that record's identity back. Leaking it costs one
 * installation's console sessions; leaking a key lets an unknown host in.
 */
class AnchorEnrollmentService
{
    public function issue(Node|Relay $installation): AnchorEnrollmentData
    {
        $token = 'anc_enroll_'.Str::random(64);
        $expiresAt = now()->addMinutes(15);

        // A node's agent columns are prefixed to keep them apart from its
        // Proxmox liveness; a relay is nothing but an installation, so its are
        // not. One place pays for that, rather than every caller.
        $prefix = $installation instanceof Node ? 'agent_' : '';

        $installation->update([
            $prefix.'enrollment_token_hash' => hash('sha256', $token),
            $prefix.'enrollment_expires_at' => $expiresAt,
        ]);

        // The same URL the enrollment response will write into the agent's
        // config, so the command shown here cannot disagree with what the
        // agent ends up using.
        $command = sprintf(
            "anchor enroll --panel-url %s --token '%s'",
            $installation->anchorPanelUrl(),
            $token,
        );

        return new AnchorEnrollmentData(
            token: $token,
            command: $command,
            expiresAt: $expiresAt->toIso8601String(),
        );
    }
}
