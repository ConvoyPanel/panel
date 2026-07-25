<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-evaluate system reservations on blocks that *delegate sub-blocks* (prefix_length_to shorter
     * than a single address) rather than hand out individual addresses.
     *
     * Reservations were previously computed as host addresses while generation materializes unit
     * boundaries, so on these blocks they landed wrong in both directions:
     *
     *  - the block's base unit was reserved as a "network address", even though a routed sub-block
     *    is perfectly delegable — on a /24 → /24 block that is the *only* unit, leaving the block
     *    with no capacity at all;
     *  - the gateway and broadcast are not unit addresses, so those reservations matched no row and
     *    silently vanished — the sub-block containing the gateway stayed allocatable.
     *
     * Mirrors AddressBlock::systemReservedAddresses() as of this migration. Spelled out in SQL
     * rather than driven through the model, per the state_reason backfill, so a later change to that
     * method cannot retroactively rewrite what this did. host() normalizes both sides to a bare
     * address, since inet equality also compares masklen.
     */
    public function up(): void
    {
        // Blocks whose units are sub-blocks rather than single addresses.
        $delegating = "b.prefix_length_to < (CASE WHEN b.version = 'ipv4' THEN 32 ELSE 128 END)";

        // The unit that owns the gateway: the gateway masked down to the output prefix.
        $gatewayUnit = 'host(network(set_masklen(b.gateway, b.prefix_length_to)))';

        // 1. Release system reservations that are no longer justified — everything except the unit
        //    holding the gateway. Assigned rows are left alone; a reserved row should never have a
        //    server attached, but releasing one out from under a VM would be worse than skipping it.
        DB::statement(<<<SQL
            UPDATE addresses a
            SET state = 'available', state_reason = NULL
            FROM address_blocks b
            WHERE a.address_block_id = b.id
              AND a.state = 'reserved'
              AND a.state_reason = 'system'
              AND a.server_id IS NULL
              AND {$delegating}
              AND (b.gateway IS NULL OR host(a.ip) <> {$gatewayUnit})
        SQL);

        // 2. Reserve the gateway's unit where it was missed. Only rows that are currently available:
        //    one already assigned to a server predates this fix and is the operator's to resolve —
        //    silently yanking a VM's address here would be a worse failure than the original bug.
        DB::statement(<<<SQL
            UPDATE addresses a
            SET state = 'reserved', state_reason = 'system'
            FROM address_blocks b
            WHERE a.address_block_id = b.id
              AND a.state = 'available'
              AND b.gateway IS NOT NULL
              AND {$delegating}
              AND b.gateway <<= network(set_masklen(b.base_ip, b.prefix_length_from))
              AND host(a.ip) = {$gatewayUnit}
        SQL);
    }

    /**
     * Not reversible: this reclassifies data using a rule the previous code could not express, and
     * restoring the old classification would re-lock the blocks it just freed.
     */
    public function down(): void {}
};
