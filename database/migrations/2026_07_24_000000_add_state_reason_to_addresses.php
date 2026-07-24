<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record *why* an address sits in its state, so `reserved` stops meaning two different things.
     * A block's network / broadcast / gateway are auto-reserved at generation time and must never
     * be handed to a VM, but until now they were indistinguishable from an operator's manual hold —
     * so the unreserve endpoint would happily free them back into the pool.
     *
     * The allocator still selects purely on `state`; this column is read by permission checks and
     * the UI only.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('state_reason')->nullable()->after('state');
        });

        // Mirrors AddressBlock::systemReservedAddresses() as of this migration: network + broadcast
        // for IPv4 blocks wider than a point-to-point /31, the subnet-router anycast for IPv6, and
        // the configured gateway. Spelled out in SQL rather than driven through the model so a later
        // change to that method can't retroactively rewrite what this backfill did.
        // host() normalizes both sides to a bare address, since inet equality also compares masklen.
        DB::statement(<<<'SQL'
            UPDATE addresses a
            SET state_reason = 'system'
            FROM address_blocks b
            WHERE a.address_block_id = b.id
              AND a.state = 'reserved'
              AND (
                  (b.version = 'ipv6' AND host(a.ip) = host(b.base_ip))
                  OR (
                      b.version = 'ipv4'
                      AND b.prefix_length_from <= 30
                      AND (
                          host(a.ip) = host(b.base_ip)
                          OR host(a.ip) = host(broadcast(set_masklen(b.base_ip, b.prefix_length_from)))
                      )
                  )
                  OR (b.gateway IS NOT NULL AND host(a.ip) = host(b.gateway))
              )
        SQL);

        // Everything else already reserved was put there by an operator.
        DB::table('addresses')
            ->where('state', 'reserved')
            ->whereNull('state_reason')
            ->update(['state_reason' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('state_reason');
        });
    }
};
