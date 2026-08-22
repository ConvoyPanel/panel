<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A node and its agent stop being two records.
 *
 * An Anchor agent runs `qm` on the box it is installed on, so it can only ever
 * serve that one host. A node *is* a PVE host. The two rows were therefore
 * always the same machine, joined by a foreign key and an admin remembering to
 * set it -- an inheritance from Coterm, which really was a separate service and
 * really could be shared. Nothing about the agent kept that property.
 *
 * The cost of the split was not just a join. It was a dropdown on the
 * new-node form asking an operator to link a record to itself, an anchor that
 * could be attached to the wrong node, and a node that could silently have
 * none.
 *
 * Three tables come out of the one:
 *
 * - `nodes` absorbs the agent: identity, secret, heartbeat, capabilities. The
 *   columns are `agent_`-prefixed because a node already has a liveness of its
 *   own -- `last_seen_at`/`status` describe whether the *Proxmox API* answers,
 *   which is a different question from whether the agent is reporting in, and
 *   collapsing the two would make an unreachable host indistinguishable from a
 *   stopped daemon.
 * - `relays` keeps what genuinely is not a node. A relay has no location, no
 *   Proxmox credentials and no capacity; folding it into `nodes` would have
 *   meant those columns going null on it and every placement query, capacity
 *   sum and node listing growing a `WHERE kind = 'node'` that one of them would
 *   eventually forget.
 * - `anchor_enrollments` holds machines that have introduced themselves and are
 *   waiting to be let in. They are not nodes yet -- a node needs a location,
 *   which is a decision nobody has made at that point -- and parking them in
 *   `nodes` with a null `location_id` is exactly the half-real row this split
 *   exists to avoid.
 *
 * Every agent column is nullable, and stays that way. A node upgraded from v4
 * has no agent at all, and inventing one to satisfy a constraint would record a
 * machine that does not exist.
 *
 * @see docs/anchor-enrollment-plan.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('public_url', 2048)->nullable();
            $table->string('panel_url_override', 2048)->nullable();
            $table->text('secret');
            $table->foreignId('enrollment_key_id')->nullable()
                ->constrained('anchor_enrollment_keys')->nullOnDelete();
            $table->string('enrollment_token_hash', 64)->nullable()->unique();
            $table->timestamp('enrollment_expires_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('protocol_min')->nullable();
            $table->unsignedSmallInteger('protocol_max')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('reported_facts')->nullable();
            $table->timestamps();
        });

        Schema::create('anchor_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            // Which of the two tables above this becomes when it is approved.
            $table->string('mode');
            $table->text('secret');
            $table->foreignId('enrollment_key_id')->nullable()
                ->constrained('anchor_enrollment_keys')->nullOnDelete();
            $table->json('reported_facts')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('protocol_min')->nullable();
            $table->unsignedSmallInteger('protocol_max')->nullable();
            $table->json('capabilities')->nullable();
            $table->timestamps();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->uuid('agent_uuid')->nullable()->unique()->after('anchor_id');
            $table->text('agent_secret')->nullable()->after('agent_uuid');
            $table->string('agent_public_url', 2048)->nullable()->after('agent_secret');
            $table->string('agent_panel_url_override', 2048)->nullable()->after('agent_public_url');
            $table->foreignId('relay_id')->nullable()->after('agent_panel_url_override')
                ->constrained('relays')->nullOnDelete();
            $table->foreignId('agent_enrollment_key_id')->nullable()->after('relay_id')
                ->constrained('anchor_enrollment_keys')->nullOnDelete();
            $table->string('agent_enrollment_token_hash', 64)->nullable()->unique()->after('agent_enrollment_key_id');
            $table->timestamp('agent_enrollment_expires_at')->nullable()->after('agent_enrollment_token_hash');
            $table->timestamp('agent_enrolled_at')->nullable()->after('agent_enrollment_expires_at');
            $table->timestamp('agent_last_seen_at')->nullable()->after('agent_enrolled_at');
            $table->string('agent_version')->nullable()->after('agent_last_seen_at');
            $table->unsignedSmallInteger('agent_protocol_min')->nullable()->after('agent_version');
            $table->unsignedSmallInteger('agent_protocol_max')->nullable()->after('agent_protocol_min');
            $table->json('agent_capabilities')->nullable()->after('agent_protocol_max');
            $table->json('agent_reported_facts')->nullable()->after('agent_capabilities');
        });

        if (Schema::hasTable('anchors')) {
            $this->migrateAnchors();
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anchor_id');
        });

        Schema::dropIfExists('anchors');
    }

    /**
     * Moves each anchor to whichever of the three tables now describes it.
     *
     * Order matters: relays are created first so the agents that route through
     * one have a row to point at.
     */
    private function migrateAnchors(): void
    {
        /** @var array<int, int> old anchors.id => new relays.id */
        $relayIds = [];

        foreach (DB::table('anchors')->where('mode', 'relay')->whereNotNull('approved_at')->get() as $relay) {
            $relayIds[$relay->id] = DB::table('relays')->insertGetId([
                'uuid' => $relay->uuid,
                'name' => $relay->name,
                'public_url' => $relay->public_url,
                'panel_url_override' => $relay->panel_url_override,
                'secret' => $relay->secret,
                'enrollment_key_id' => $relay->enrollment_key_id,
                'enrollment_token_hash' => $relay->enrollment_token_hash,
                'enrollment_expires_at' => $relay->enrollment_expires_at,
                'enrolled_at' => $relay->enrolled_at,
                'last_seen_at' => $relay->last_seen_at,
                'version' => $relay->version,
                'protocol_min' => $relay->protocol_min,
                'protocol_max' => $relay->protocol_max,
                'capabilities' => $relay->capabilities,
                'reported_facts' => $relay->reported_facts,
                'created_at' => $relay->created_at,
                'updated_at' => $relay->updated_at,
            ]);
        }

        // Anything never approved is still a claim, whichever mode it claimed.
        foreach (DB::table('anchors')->whereNull('approved_at')->get() as $pending) {
            DB::table('anchor_enrollments')->insert([
                'uuid' => $pending->uuid,
                'name' => $pending->name,
                'mode' => $pending->mode,
                'secret' => $pending->secret,
                'enrollment_key_id' => $pending->enrollment_key_id,
                'reported_facts' => $pending->reported_facts,
                'enrolled_at' => $pending->enrolled_at,
                'last_seen_at' => $pending->last_seen_at,
                'version' => $pending->version,
                'protocol_min' => $pending->protocol_min,
                'protocol_max' => $pending->protocol_max,
                'capabilities' => $pending->capabilities,
                'created_at' => $pending->created_at,
                'updated_at' => $pending->updated_at,
            ]);
        }

        $agents = DB::table('anchors')
            ->where('mode', 'agent')
            ->whereNotNull('approved_at')
            ->get()
            ->keyBy('id');

        foreach (DB::table('nodes')->whereNotNull('anchor_id')->get(['id', 'anchor_id']) as $node) {
            $agent = $agents->get($node->anchor_id);

            if ($agent === null) {
                continue;
            }

            DB::table('nodes')->where('id', $node->id)->update([
                'agent_uuid' => $agent->uuid,
                'agent_secret' => $agent->secret,
                'agent_public_url' => $agent->public_url,
                'agent_panel_url_override' => $agent->panel_url_override,
                'relay_id' => $agent->relay_id === null ? null : ($relayIds[$agent->relay_id] ?? null),
                'agent_enrollment_key_id' => $agent->enrollment_key_id,
                'agent_enrollment_token_hash' => $agent->enrollment_token_hash,
                'agent_enrollment_expires_at' => $agent->enrollment_expires_at,
                'agent_enrolled_at' => $agent->enrolled_at,
                'agent_last_seen_at' => $agent->last_seen_at,
                'agent_version' => $agent->version,
                'agent_protocol_min' => $agent->protocol_min,
                'agent_protocol_max' => $agent->protocol_max,
                'agent_capabilities' => $agent->capabilities,
                'agent_reported_facts' => $agent->reported_facts,
            ]);
        }

        /*
         * An approved agent attached to no node had nothing to serve: it could
         * not open a console, because the only VMs it can reach are the ones on
         * its own host and no host was pointing at it. Rather than drop it, it
         * goes back to being a claim, which is what it effectively was -- an
         * installation nobody had finished placing.
         */
        $attached = DB::table('nodes')->whereNotNull('anchor_id')->pluck('anchor_id')->all();

        foreach ($agents->whereNotIn('id', $attached) as $orphan) {
            DB::table('anchor_enrollments')->insert([
                'uuid' => $orphan->uuid,
                'name' => $orphan->name,
                'mode' => 'agent',
                'secret' => $orphan->secret,
                'enrollment_key_id' => $orphan->enrollment_key_id,
                'reported_facts' => $orphan->reported_facts,
                'enrolled_at' => $orphan->enrolled_at,
                'last_seen_at' => $orphan->last_seen_at,
                'version' => $orphan->version,
                'protocol_min' => $orphan->protocol_min,
                'protocol_max' => $orphan->protocol_max,
                'capabilities' => $orphan->capabilities,
                'created_at' => $orphan->created_at,
                'updated_at' => $orphan->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::create('anchors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('mode');
            $table->string('public_url')->nullable();
            $table->string('panel_url_override', 2048)->nullable();
            $table->text('secret');
            $table->foreignId('relay_id')->nullable()->constrained('anchors')->nullOnDelete();
            $table->foreignId('enrollment_key_id')->nullable()
                ->constrained('anchor_enrollment_keys')->nullOnDelete();
            $table->string('enrollment_token_hash', 64)->nullable()->unique();
            $table->timestamp('enrollment_expires_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('protocol_min')->nullable();
            $table->unsignedSmallInteger('protocol_max')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('reported_facts')->nullable();
            $table->timestamps();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('anchor_id')->nullable()->constrained('anchors')->nullOnDelete();
        });

        $relayIds = [];

        foreach (DB::table('relays')->get() as $relay) {
            $relayIds[$relay->id] = DB::table('anchors')->insertGetId([
                'uuid' => $relay->uuid,
                'name' => $relay->name,
                'mode' => 'relay',
                'public_url' => $relay->public_url,
                'panel_url_override' => $relay->panel_url_override,
                'secret' => $relay->secret,
                'enrollment_key_id' => $relay->enrollment_key_id,
                'enrollment_token_hash' => $relay->enrollment_token_hash,
                'enrollment_expires_at' => $relay->enrollment_expires_at,
                'enrolled_at' => $relay->enrolled_at,
                'approved_at' => $relay->created_at,
                'last_seen_at' => $relay->last_seen_at,
                'version' => $relay->version,
                'protocol_min' => $relay->protocol_min,
                'protocol_max' => $relay->protocol_max,
                'capabilities' => $relay->capabilities,
                'reported_facts' => $relay->reported_facts,
                'created_at' => $relay->created_at,
                'updated_at' => $relay->updated_at,
            ]);
        }

        foreach (DB::table('nodes')->whereNotNull('agent_uuid')->get() as $node) {
            $anchorId = DB::table('anchors')->insertGetId([
                'uuid' => $node->agent_uuid,
                'name' => $node->display_name,
                'mode' => 'agent',
                'public_url' => $node->agent_public_url,
                'panel_url_override' => $node->agent_panel_url_override,
                'secret' => $node->agent_secret,
                'relay_id' => $node->relay_id === null ? null : ($relayIds[$node->relay_id] ?? null),
                'enrollment_key_id' => $node->agent_enrollment_key_id,
                'enrollment_token_hash' => $node->agent_enrollment_token_hash,
                'enrollment_expires_at' => $node->agent_enrollment_expires_at,
                'enrolled_at' => $node->agent_enrolled_at,
                'approved_at' => $node->agent_enrolled_at ?? $node->created_at,
                'last_seen_at' => $node->agent_last_seen_at,
                'version' => $node->agent_version,
                'protocol_min' => $node->agent_protocol_min,
                'protocol_max' => $node->agent_protocol_max,
                'capabilities' => $node->agent_capabilities,
                'reported_facts' => $node->agent_reported_facts,
                'created_at' => $node->created_at,
                'updated_at' => $node->updated_at,
            ]);

            DB::table('nodes')->where('id', $node->id)->update(['anchor_id' => $anchorId]);
        }

        foreach (DB::table('anchor_enrollments')->get() as $pending) {
            DB::table('anchors')->insert([
                'uuid' => $pending->uuid,
                'name' => $pending->name,
                'mode' => $pending->mode,
                'public_url' => null,
                'secret' => $pending->secret,
                'enrollment_key_id' => $pending->enrollment_key_id,
                'enrolled_at' => $pending->enrolled_at,
                'approved_at' => null,
                'last_seen_at' => $pending->last_seen_at,
                'version' => $pending->version,
                'protocol_min' => $pending->protocol_min,
                'protocol_max' => $pending->protocol_max,
                'capabilities' => $pending->capabilities,
                'reported_facts' => $pending->reported_facts,
                'created_at' => $pending->created_at,
                'updated_at' => $pending->updated_at,
            ]);
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('relay_id');
            $table->dropConstrainedForeignId('agent_enrollment_key_id');
            $table->dropColumn([
                'agent_uuid', 'agent_secret', 'agent_public_url', 'agent_panel_url_override',
                'agent_enrollment_token_hash', 'agent_enrollment_expires_at', 'agent_enrolled_at',
                'agent_last_seen_at', 'agent_version', 'agent_protocol_min', 'agent_protocol_max',
                'agent_capabilities', 'agent_reported_facts',
            ]);
        });

        Schema::dropIfExists('anchor_enrollments');
        Schema::dropIfExists('relays');
    }
};
