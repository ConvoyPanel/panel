<?php

namespace App\Http\Controllers\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Anchor\ConsumeEnrollmentRequest;
use App\Models\Anchor;
use App\Services\Anchor\AnchorEnrollmentKeyService;
use App\Services\Anchor\AnchorSelfRegistrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EnrollmentController
{
    public function __construct(
        private AnchorSelfRegistrationService $selfRegistration,
    ) {}

    public function __invoke(ConsumeEnrollmentRequest $request)
    {
        $token = $request->string('token')->toString();

        /*
         * Which question is being asked is decided by the token's shape, not by
         * which lookup happens to find a row.
         *
         * Looking up both and taking whichever hits would mean a mistyped
         * rotation token falls through and gets evaluated as an attempt to
         * enroll a stranger -- the two paths have very different consequences,
         * so which one a request is on must not depend on a query missing.
         */
        $anchor = Str::startsWith($token, AnchorEnrollmentKeyService::TOKEN_PREFIX)
            ? $this->selfRegister($request, $token)
            : $this->rotate($token);

        return response()->json(['config' => $this->config($anchor)]);
    }

    /** A machine the panel has never seen, holding a valid enrollment key. */
    private function selfRegister(ConsumeEnrollmentRequest $request, string $token): Anchor
    {
        $anchor = $this->selfRegistration->register(
            token: $token,
            mode: $request->mode(),
            report: $request->report(),
        );

        /*
         * No actor: the enrolling machine is not a panel principal, and casting
         * it as one would put a host in the same column as the admins. "Who
         * let this in" is answered through the key named here, which leads to
         * the admin who cut it.
         */
        Audit::record(
            AuditEvent::ADMIN_ANCHOR_SELF_ENROLLED,
            subject: $anchor,
            properties: [
                'name' => $anchor->name,
                'mode' => $anchor->mode->value,
                'enrollment_key' => $anchor->enrollmentKey?->name,
                'hostname' => $anchor->reported_facts['hostname'] ?? null,
                'source_ip' => $anchor->reported_facts['observed_source_ip'] ?? null,
            ],
        );

        return $anchor;
    }

    /** An installation the panel already has a row for, being re-keyed. */
    private function rotate(string $token): Anchor
    {
        return DB::transaction(function () use ($token) {
            $anchor = Anchor::where(
                'enrollment_token_hash',
                hash('sha256', $token),
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
    }

    /**
     * What the agent writes to disk.
     *
     * `public_url` is deliberately absent. It describes how the *panel* reaches
     * the agent, the agent has never read it (it serves the same routes
     * regardless), and mirroring it into the TOML meant a correction could not
     * be made without re-enrolling the box.
     *
     * @return array<string, mixed>
     */
    private function config(Anchor $anchor): array
    {
        return [
            'mode' => $anchor->mode->value,
            'listen_addr' => $anchor->mode === AnchorMode::AGENT ? '127.0.0.1:2115' : '0.0.0.0:2115',
            'installation_id' => $anchor->uuid,
            'secret' => $anchor->secret,
            'panel_url' => $anchor->panelUrl().'/',
            'agent' => ['qm_path' => '/usr/sbin/qm'],
        ];
    }
}
