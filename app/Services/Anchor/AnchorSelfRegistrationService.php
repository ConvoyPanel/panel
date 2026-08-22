<?php

namespace App\Services\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Models\AnchorEnrollment;
use App\Models\AnchorEnrollmentKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Turns "a machine presented a valid key" into a claim on the panel's attention.
 *
 * Not into a node. A node needs a location, and nobody has decided one yet;
 * that is what approval is for. Everything the machine says about itself is
 * kept as evidence for that decision, and nothing it says grants privilege.
 */
class AnchorSelfRegistrationService
{
    /**
     * @param  array<string, mixed>  $report
     *
     * @throws UnprocessableEntityHttpException when the key cannot admit it
     */
    public function register(string $token, AnchorMode $mode, array $report): AnchorEnrollment
    {
        return DB::transaction(function () use ($token, $mode, $report) {
            /*
             * Locked because two machines booting from one image present the
             * same single-use key at the same moment. Without it both read
             * `uses = 0` and both get in, which makes max_uses a suggestion.
             */
            $key = AnchorEnrollmentKey::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if ($key === null || ! $key->isUsable()) {
                // One message for "no such key", "revoked", "expired" and "used
                // up": the presenter is unauthenticated, and which of those it
                // is tells them something they have not earned the right to know.
                throw new UnprocessableEntityHttpException('The enrollment key is invalid or expired.');
            }

            if (! $key->permits($mode)) {
                throw new UnprocessableEntityHttpException(
                    "This enrollment key does not admit an installation in {$mode->value} mode.",
                );
            }

            $enrollment = AnchorEnrollment::create([
                'uuid' => (string) Str::uuid(),
                'name' => $this->name($report, $key),
                'mode' => $mode,
                'secret' => Str::random(64),
                'enrollment_key_id' => $key->id,
                'reported_facts' => $report,
                'enrolled_at' => now(),
            ]);

            $key->increment('uses');
            $key->forceFill(['last_used_at' => now()])->save();

            return $enrollment;
        });
    }

    /**
     * A name an operator will recognise in the approval queue.
     *
     * Prefers what the machine calls itself, because that is what the person
     * who racked it will search for. Falls back to the key's name plus a
     * discriminator, so a rack enrolling from one key does not produce eight
     * rows called "Rack 4".
     *
     * @param  array<string, mixed>  $report
     */
    private function name(array $report, AnchorEnrollmentKey $key): string
    {
        foreach (['hostname', 'pve_node_name'] as $field) {
            $value = $report[$field] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 191, '');
            }
        }

        return Str::limit($key->name, 180, '').' '.Str::lower(Str::random(6));
    }
}
