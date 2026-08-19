<?php

namespace App\Http\Controllers\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\Anchor\ConsumeEnrollmentRequest;
use App\Models\Anchor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EnrollmentController
{
    public function __invoke(ConsumeEnrollmentRequest $request)
    {
        $anchor = DB::transaction(function () use ($request) {
            $anchor = Anchor::where(
                'enrollment_token_hash',
                hash('sha256', $request->string('token')->toString()),
            )->lockForUpdate()->first();

            if ($anchor === null || $anchor->enrollment_expires_at?->isPast()) {
                throw new UnprocessableEntityHttpException('The enrollment token is invalid or expired.');
            }

            $anchor->update([
                'enrollment_token_hash' => null,
                'enrollment_expires_at' => null,
                'enrolled_at' => now(),
                /*
                 * Enrolling is the only path that hands the secret out, so it
                 * is the only place that can rotate it -- and it has to, or the
                 * enrollment token is a fresh courier delivering the same
                 * payload forever: any copy of anchor.toml that ever leaked
                 * stays valid, and re-enrolling, the one action that looks like
                 * remediation, hands the identical secret back.
                 *
                 * Rotating here makes "reissue the command, run it again" the
                 * remediation. The cost is deliberate: the previous
                 * installation's bearer stops matching immediately and its
                 * console sessions, signed with the old secret, die with it.
                 */
                'secret' => Str::random(64),
            ]);

            return $anchor;
        });

        return response()->json([
            'config' => [
                'mode' => $anchor->mode->value,
                'listen_addr' => $anchor->mode === AnchorMode::AGENT ? '127.0.0.1:2115' : '0.0.0.0:2115',
                'installation_id' => $anchor->uuid,
                'secret' => $anchor->secret,
                'panel_url' => $anchor->panelUrl().'/',
                'public_url' => $anchor->public_url,
                'agent' => ['qm_path' => '/usr/sbin/qm'],
            ],
        ]);
    }
}
