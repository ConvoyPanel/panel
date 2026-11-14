<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ISO sizes were stored in whole mebibytes, rounded down, like server memory
 * and disk. An ISO's size comes from Proxmox in bytes, so it now keeps them.
 * Existing rows are converted; what the rounding already dropped can't be
 * recovered, but each ISO's size stays what the panel showed before.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('iso_library')->update(['size' => DB::raw('size * 1048576')]);
    }

    public function down(): void
    {
        DB::table('iso_library')->update(['size' => DB::raw('FLOOR(size / 1048576)')]);
    }
};
