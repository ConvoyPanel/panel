<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reserve buffer (headroom): the amount of a storage's *free* space Convoy
     * must never allocate into. Stored in MiB (via StorageSizeCast) to match the
     * other size columns. Null = no reserve. Capacity/usage themselves are read
     * live from Proxmox — this is the only operator knob on top of that truth.
     */
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->unsignedBigInteger('reserved_bytes')->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn('reserved_bytes');
        });
    }
};
