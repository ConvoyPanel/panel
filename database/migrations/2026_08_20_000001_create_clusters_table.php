<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A storage scope: one row per PVE cluster, plus one singleton row per
 * standalone node.
 *
 * Identity is the cluster's root CA fingerprint (`/etc/pve/pve-root-ca.pem`,
 * read via `/nodes/{node}/certificates/info`), never its name. The CA is
 * generated once at install time, becomes the cluster's CA when that host
 * founds one, is replicated to every member by pmxcfs, and cannot be shared by
 * two clusters -- while two clusters both named `proxmox` are commonplace, and
 * treating them as one linked shared pools across nodes that cannot reach
 * them.
 *
 * `fingerprint` is null for a standalone node's singleton scope. That is
 * deliberate, not a missing value: a node whose `/cluster/status` carries no
 * `type=cluster` row is not in a cluster *now*, whatever certificate it
 * happens to hold -- a node separated from a cluster without a reinstall keeps
 * the old CA, and keying standalone scopes by certificate would glue such a
 * node straight back onto the cluster it left.
 *
 * `member_names` is the tripwire, not the identity. If a poll reports a member
 * set with no overlap at all with what is stored, either the cluster was
 * renamed node-by-node wholesale or two clusters share a CA (a separated node
 * re-clustered without regenerating certificates). Both deserve a human, so
 * the row is flagged rather than silently updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint')->nullable()->unique();
            // Display label from /cluster/status; carries no identity.
            $table->string('name')->nullable();
            $table->json('member_names')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->string('flag_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};
