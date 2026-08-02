<?php

namespace App\Services\Anchor;

use App\Data\Anchor\AnchorEnrollmentData;
use App\Models\Anchor;
use Illuminate\Support\Str;

class AnchorEnrollmentService
{
    public function issue(Anchor $anchor): AnchorEnrollmentData
    {
        $token = 'anc_enroll_'.Str::random(64);
        $expiresAt = now()->addMinutes(15);

        $anchor->update([
            'enrollment_token_hash' => hash('sha256', $token),
            'enrollment_expires_at' => $expiresAt,
        ]);

        // The same URL the enrollment response will write into the agent's
        // config, so the command shown here cannot disagree with what the
        // agent ends up using.
        $command = sprintf(
            "anchor enroll --panel-url %s --token '%s'",
            $anchor->panelUrl(),
            $token,
        );

        return new AnchorEnrollmentData(
            token: $token,
            command: $command,
            expiresAt: $expiresAt->toIso8601String(),
        );
    }
}
