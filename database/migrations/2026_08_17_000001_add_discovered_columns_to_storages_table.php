<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What Proxmox says about a storage, recorded beside what the operator declared.
 *
 * Every existing column on `storages` is typed in by hand -- `size`, the six
 * content flags, `is_shareable` -- and every one of them can drift from the host
 * without anything noticing. PVE already knows all of it, and the poll's
 * `/cluster/resources` call already carries it on the `type=storage` rows, so
 * discovery costs no extra request.
 *
 * Additive on purpose. The declared columns stay authoritative for now and
 * remain the answer when a node is unreachable; these are what the UI can show
 * as the truth when the node is up, and what a later phase can promote.
 *
 * `pve_type` is the load-bearing one. It is what distinguishes a thin backend --
 * where committed legitimately exceeds written bytes -- from a thick one where
 * that gap means something is wrong, and it is what identifies a Proxmox Backup
 * Server datastore without PBS needing any model of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            // PVE's `plugintype`: dir, lvmthin, zfspool, nfs, cifs, rbd, cephfs, pbs, ...
            $table->string('pve_type')->nullable()->after('name');
            // PVE's own `shared` flag -- the authority `is_shareable` only guesses at.
            $table->boolean('pve_shared')->nullable()->after('pve_type');
            // PVE's comma-separated content list, e.g. `images,rootdir` or `backup`.
            $table->string('pve_content')->nullable()->after('pve_shared');

            // Capacity as last reported. Nullable rather than zero-defaulted: a
            // store Convoy has never reached is not a store with no space, and
            // zero would be indistinguishable from a full one.
            $table->unsignedBigInteger('discovered_total')->nullable()->after('pve_content');
            $table->unsignedBigInteger('discovered_used')->nullable()->after('discovered_total');
            $table->timestamp('discovered_at')->nullable()->after('discovered_used');
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn([
                'pve_type',
                'pve_shared',
                'pve_content',
                'discovered_total',
                'discovered_used',
                'discovered_at',
            ]);
        });
    }
};
