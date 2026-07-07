<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per VM disk. A server has exactly one `is_primary` disk (the
     * OS/boot disk produced by the template clone) plus zero or more secondary
     * data disks, each on its own storage.
     *
     * Expand-first: `servers.storage_id` / `servers.disk` are KEPT as the
     * primary-disk pointer for now (the clone still reads `server->storage`).
     * A later slice turns them into `primaryDisk()` accessors and drops them.
     * `size` is MiB (StorageSizeCast), matching `servers.disk`. `interface`
     * (e.g. `scsi1`) is assigned at build time, so it's null until then.
     */
    public function up(): void
    {
        Schema::create('server_disks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_id')->constrained();
            $table->unsignedBigInteger('size');
            $table->string('interface')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('disk_index')->default(0);

            // A server has at most one primary disk, and never two disks on the
            // same interface slot (once assigned).
            $table->unique(['server_id', 'interface']);
        });

        // Backfill: every existing server gets a primary disk row from its
        // current (storage_id, disk). Both `disk` and `size` are MiB, so copy
        // straight across.
        DB::table('server_disks')->insertUsing(
            ['server_id', 'storage_id', 'size', 'interface', 'is_primary', 'disk_index'],
            DB::table('servers')->select(
                'id',
                'storage_id',
                'disk',
                DB::raw('NULL as interface'),
                DB::raw('true as is_primary'),
                DB::raw('0 as disk_index'),
            ),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('server_disks');
    }
};
