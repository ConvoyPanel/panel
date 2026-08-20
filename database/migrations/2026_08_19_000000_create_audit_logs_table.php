<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the 2022 Pterodactyl-derived activity log with the audit log described in
     * docs/audit-log-plan.md.
     *
     * The old tables are dropped rather than migrated: nothing ever wrote to them (there was not a
     * single call site, and `ActivityLogService` would have failed on its first insert because it
     * assigned a column that did not exist), so there is no history to preserve. The 2022
     * migrations are left in the tree so existing installs still have a coherent history.
     *
     * `activity_log_subjects` goes away entirely — a many-to-many subject buys nothing once the
     * model is "subject = the thing acted on, actor = who acted".
     */
    public function up(): void
    {
        Schema::dropIfExists('activity_log_subjects');
        Schema::dropIfExists('activity_logs');

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Groups the several rows a single user action can produce (a bulk delete, say).
            $table->uuid('batch')->nullable();

            // An App\Enums\Audit\AuditEvent value.
            $table->string('event');

            // User, or SystemActor for panel-wide application tokens. Null means the action could
            // not be attributed — rare enough that it should be treated as suspicious.
            // Columns declared by hand rather than via nullableNumericMorphs(): that helper adds its
            // own (type, id) index, which would be a strict prefix of the (type, id, created_at)
            // composite below and therefore pure write overhead on an append-only table.
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            // The actor's display name, copied at write time. No model in this panel soft-deletes,
            // so the morph above resolves to null the moment the user is removed — and an audit
            // log that forgets who acted the instant you delete their account is not an audit log.
            // Denormalised deliberately: it is a snapshot of who they were then, not a live join.
            $table->string('actor_label')->nullable();

            // Set when the action arrived over the API, so a leaked key's blast radius is visible.
            // nullOnDelete, not cascade: revoking a token must not erase what it did.
            $table->foreignId('api_token_id')->nullable()
                ->constrained('personal_access_tokens')->nullOnDelete();

            // Whatever was acted on. Nullable because a few events (panel settings) have no subject.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // 45 characters is the longest possible IPv6 representation.
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->json('properties');

            // Append-only, so `created_at` alone. The predecessor's bespoke `timestamp` column is
            // exactly what drifted from the model and broke the nightly prune; stick to the
            // Laravel convention this time.
            $table->timestamp('created_at')->nullable();

            // The client activity feed and the per-user admin view are both "newest first, for one
            // morph target", which is what these composites serve. They also cover plain
            // (type, id) lookups by prefix, so no separate morph index is needed.
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['actor_type', 'actor_id', 'created_at']);
            $table->index('event');
            $table->index('batch');

            // Drives the pruner's range scan.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        // Recreated at their final 2022 shape so a rollback lands somewhere coherent, even though
        // nothing reads them any more.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch')->nullable();
            $table->string('event')->index();
            $table->string('ip');
            $table->text('description')->nullable();
            $table->nullableNumericMorphs('actor');
            $table->json('properties');
            $table->timestamp('timestamp')->useCurrent();
        });

        Schema::create('activity_log_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained()->cascadeOnDelete();
            $table->numericMorphs('subject');
        });
    }
};
