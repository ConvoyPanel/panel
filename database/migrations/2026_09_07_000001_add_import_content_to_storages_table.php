<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which storages accept PVE's `import` content.
 *
 * Direct import needs somewhere on the node to put a disk image before
 * `qm create --import-from` can read it, and PVE gates that behind a content
 * type an operator has to enable deliberately -- it is off by default even on
 * directory storages. Like every other `stores_*` flag this is Proxmox's answer
 * read out of `storage.cfg`, never an operator's tick box here, so the panel can
 * tell an admin *why* a node cannot take an image instead of failing at build.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->boolean('stores_import')->default(false)->after('stores_iso');
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn('stores_import');
        });
    }
};
