<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an installation create its own row, and holds it at arm's length until a
 * human says otherwise.
 *
 * Three columns, one relaxation:
 *
 * - `enrollment_key_id` -- provenance. "Who let this box in" is answerable
 *   through the key, and through the key's own creation event, to the admin who
 *   cut it.
 * - `reported_facts` -- what the machine said about itself. Deliberately a
 *   document rather than columns: it is evidence, not state. Nothing schedules
 *   against it, and the fields it carries are whatever the agent version that
 *   sent it knew how to gather. Slice 3 promotes the parts it trusts into
 *   `nodes`, where they *are* state and get typed columns.
 * - `approved_at` -- the gate. A self-registered Anchor is a stranger holding a
 *   valid key, which is a claim, not a credential-plus-permission.
 *
 * Existing rows are backfilled to `created_at`: every Anchor that exists today
 * was typed in by an admin, so it is approved by construction. Backfilling is
 * safe here in a way that fabricating an anchor for a node never is -- this
 * records a decision that demonstrably happened, rather than inventing a
 * machine that does not exist.
 *
 * `public_url` becomes nullable because a machine that just introduced itself
 * has not yet been told how the panel will reach it back. The alternative --
 * storing a guess derived from the request's source address -- puts an
 * unvalidated value in the column the console dials, where it looks settled and
 * fails at first use. Null says the true thing, and the approval step is where
 * it stops being null.
 *
 * @see docs/anchor-enrollment-plan.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anchors', function (Blueprint $table) {
            $table->foreignId('enrollment_key_id')
                ->nullable()
                ->after('relay_id')
                ->constrained('anchor_enrollment_keys')
                ->nullOnDelete();

            $table->json('reported_facts')->nullable()->after('capabilities');
            $table->timestamp('approved_at')->nullable()->after('enrolled_at');
        });

        DB::table('anchors')->whereNull('approved_at')->update([
            'approved_at' => DB::raw('created_at'),
        ]);

        Schema::table('anchors', function (Blueprint $table) {
            $table->string('public_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restore the NOT NULL contract before re-imposing it. Any row still
        // holding null never completed approval, so there is no correct address
        // to put back -- an empty string is the honest placeholder for a value
        // that was never established.
        DB::table('anchors')->whereNull('public_url')->update(['public_url' => '']);

        Schema::table('anchors', function (Blueprint $table) {
            $table->string('public_url')->nullable(false)->change();
            $table->dropColumn(['reported_facts', 'approved_at']);
            $table->dropConstrainedForeignId('enrollment_key_id');
        });
    }
};
