<?php

namespace App\Services\Anchor;

use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;

/**
 * Finds whichever record owns a bearer credential.
 *
 * An installation's identity outlives the table it sits in: a machine enrolls
 * into `anchor_enrollments`, keeps heartbeating while it waits, and is then
 * promoted into `nodes` or `relays` carrying the same uuid and secret. Its
 * bearer token is valid across that move, which is the point -- approval must
 * not require the agent to re-enroll.
 *
 * So the lookup asks all three. The uuid is unique within each table and is
 * generated per installation, so a collision across them is not a case worth
 * handling; the first hit is the answer.
 */
class AnchorIdentityService
{
    /** @return Node|Relay|AnchorEnrollment|null */
    public function resolve(?string $bearer): mixed
    {
        [$uuid, $secret] = array_pad(explode('.', $bearer ?? '', 2), 2, null);

        if ($uuid === null || $secret === null) {
            return null;
        }

        $installation = Node::query()->where('agent_uuid', $uuid)->first()
            ?? Relay::query()->where('uuid', $uuid)->first()
            ?? AnchorEnrollment::query()->where('uuid', $uuid)->first();

        if ($installation === null) {
            return null;
        }

        $known = $installation->anchorSecret();

        // hash_equals over a plain comparison for the usual reason, and a null
        // guard because a node row can exist with no agent at all.
        return $known !== null && hash_equals($known, $secret) ? $installation : null;
    }
}
