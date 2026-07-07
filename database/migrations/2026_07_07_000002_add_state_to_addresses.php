<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a tri-state lifecycle column to addresses (available / assigned / reserved), replacing the
     * binary "server_id IS NULL == free" assumption. Lets IPs be held out of the pool (reserved) and
     * lets network/broadcast/gateway be auto-reserved instead of handed to VMs.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('state')->default('available')->after('server_id');
        });

        // Backfill from the pre-existing signal: an attached address is 'assigned', the rest 'available'.
        DB::table('addresses')->whereNotNull('server_id')->update(['state' => 'assigned']);

        // The allocator now selects on state, so repoint the partial index from server_id to state.
        DB::statement('DROP INDEX IF EXISTS addresses_free_by_block_ip_idx');
        DB::statement("CREATE INDEX addresses_available_by_block_ip_idx ON addresses (address_block_id, ip) WHERE state = 'available'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS addresses_available_by_block_ip_idx');
        DB::statement('CREATE INDEX addresses_free_by_block_ip_idx ON addresses (address_block_id, ip) WHERE server_id IS NULL');

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
