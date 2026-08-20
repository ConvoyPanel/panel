<?php

use App\Models\PersonalAccessToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A credential that admits a *new* machine, as opposed to one that re-keys a
 * machine the panel already knows.
 *
 * `anchors.enrollment_token_hash` answers the second question: it is minted for
 * one existing row and rotates that installation's secret. It cannot answer the
 * first, because it is bound to a record that must already exist -- which is
 * exactly what makes adding an Anchor a form-filling exercise today.
 *
 * A key here is bound to nothing. The machine that presents it identifies
 * itself, and the panel creates the record from what it reports. The two
 * credentials stay separate rather than one growing a nullable `anchor_id`,
 * because they have different blast radii: leaking a rotation token costs one
 * installation's console sessions; leaking a key lets an unknown host in.
 * Anything that reads like "and also, optionally, admit strangers" on the
 * rotation path is a mistake waiting to be made.
 *
 * Only the SHA-256 of the token is stored. The plaintext is shown once, at
 * creation, the same way {@see PersonalAccessToken} treats a panel-wide API
 * token.
 *
 * @see docs/anchor-enrollment-plan.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anchor_enrollment_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('token_hash', 64)->unique();

            /*
             * Which mode a presenting installation may claim, or null for
             * either. A key cut for a rack of Proxmox hosts has no business
             * standing up a relay.
             */
            $table->string('mode')->nullable();

            /*
             * Null max_uses means unlimited -- deliberately expressible, and
             * deliberately not the default the API hands out. A reusable key
             * baked into a machine image is the whole point of this feature for
             * a fleet; it is also the shape most worth being explicit about.
             */
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses')->default(0);

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            /*
             * Who let these machines in. Nulled rather than cascaded when that
             * admin is deleted: the key outlives the person, and the audit log
             * keeps the name via `actor_label` regardless.
             */
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anchor_enrollment_keys');
    }
};
