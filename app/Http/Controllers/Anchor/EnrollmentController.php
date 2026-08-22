<?php

namespace App\Http\Controllers\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Anchor\ConsumeEnrollmentRequest;
use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;
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
        $installation = Str::startsWith($token, AnchorEnrollmentKeyService::TOKEN_PREFIX)
            ? $this->selfRegister($request, $token)
            : $this->rotate($token);

        return response()->json(['config' => $this->config($installation)]);
    }

    /** A machine the panel has never seen, holding a valid enrollment key. */
    private function selfRegister(ConsumeEnrollmentRequest $request, string $token): AnchorEnrollment
    {
        $enrollment = $this->selfRegistration->register(
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
            subject: $enrollment,
            properties: [
                'name' => $enrollment->name,
                'mode' => $enrollment->mode->value,
                'enrollment_key' => $enrollment->enrollmentKey?->name,
                'hostname' => $enrollment->reported('hostname'),
                'source_ip' => $enrollment->reported('observed_source_ip'),
            ],
        );

        return $enrollment;
    }

    /**
     * An installation the panel already has a row for, being re-keyed.
     *
     * Looks in both tables that can hold one. A node's columns are prefixed and
     * a relay's are not, which is the price of the node and its agent being one
     * record -- paid here, in one place, rather than by every reader.
     */
    private function rotate(string $token): Node|Relay
    {
        return DB::transaction(function () use ($token) {
            $hash = hash('sha256', $token);

            $node = Node::where('agent_enrollment_token_hash', $hash)->lockForUpdate()->first();
            $relay = $node === null
                ? Relay::where('enrollment_token_hash', $hash)->lockForUpdate()->first()
                : null;

            $installation = $node ?? $relay;
            $expiresAt = $node?->agent_enrollment_expires_at ?? $relay?->enrollment_expires_at;

            if ($installation === null || $expiresAt?->isPast()) {
                throw new UnprocessableEntityHttpException('The enrollment token is invalid or expired.');
            }

            $prefix = $node !== null ? 'agent_' : '';

            $installation->update([
                $prefix.'enrollment_token_hash' => null,
                $prefix.'enrollment_expires_at' => null,
                $prefix.'enrolled_at' => now(),
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
                $prefix.'secret' => Str::random(64),
            ]);

            return $installation;
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
    private function config(Node|Relay|AnchorEnrollment $installation): array
    {
        $mode = $installation->anchorMode();

        return [
            'mode' => $mode->value,
            'listen_addr' => $mode === AnchorMode::AGENT ? '127.0.0.1:2115' : '0.0.0.0:2115',
            'installation_id' => $installation->anchorUuid(),
            'secret' => $installation->anchorSecret(),
            'panel_url' => $installation->anchorPanelUrl().'/',
            'agent' => ['qm_path' => '/usr/sbin/qm'],
        ];
    }
}
