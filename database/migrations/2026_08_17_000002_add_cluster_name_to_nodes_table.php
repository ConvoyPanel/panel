<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which PVE cluster a node belongs to, discovered by the poll.
 *
 * Convoy models a node as an independent Proxmox host, which is right for a
 * standalone install and wrong for a cluster: `storage.cfg` lives in `/etc/pve`
 * and replicates to every member, so a storage id is unique within a cluster and
 * means nothing across one. Without knowing the cluster, Convoy cannot tell one
 * SAN mounted by four hosts from four separate disks that happen to share the
 * name `local-lvm`.
 *
 * Null means standalone, which is a real answer rather than a missing one --
 * PVE reports no `type=cluster` row for an unclustered host.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('cluster_name')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('cluster_name');
        });
    }
};
