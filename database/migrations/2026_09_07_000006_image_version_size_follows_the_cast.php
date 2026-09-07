<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts this column back on the convention every other size column follows.
 *
 * It was briefly `size_bytes`, opted out of `StorageSizeCast` on the grounds
 * that its exact byte value mattered. It does not. The column feeds one thing --
 * the total on a download progress bar -- and losing under a mebibyte off a
 * multi-gigabyte total is invisible. The verification that does need exactness
 * is the sha256, which Proxmox checks, and residency is answered by a
 * hash-derived filename; neither reads this.
 *
 * Its nearest siblings, `iso_library.size` and `server_disks.size`, are bigints
 * that use the cast anyway. One column keeping its own convention -- and its own
 * name for the same idea -- costs more than the mebibyte it saves.
 *
 * `disks[].size` and `disks[].virtual_size` stay raw bytes, because casts do not
 * reach inside a JSON column. That is worth knowing rather than hiding: the
 * column is MiB on disk, the JSON beside it is bytes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_versions', function (Blueprint $table) {
            $table->renameColumn('size_bytes', 'size');
        });

        // The column held bytes; the cast reads it as mebibytes. Scale what is
        // already there, or every existing version reports a size 2^20 too big.
        DB::table('image_versions')->update([
            'size' => DB::raw('floor(size / 1048576)'),
        ]);
    }

    public function down(): void
    {
        DB::table('image_versions')->update([
            'size' => DB::raw('size * 1048576'),
        ]);

        Schema::table('image_versions', function (Blueprint $table) {
            $table->renameColumn('size', 'size_bytes');
        });
    }
};
