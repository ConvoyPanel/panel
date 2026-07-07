<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * v10 is Postgres-only, so store IP addresses in the native `inet` type instead of text.
     * This gives the database correct numeric ordering (10.0.0.2 < 10.0.0.10), arithmetic
     * (ip + 1), subnet containment (ip << cidr), and window-function gap detection — the
     * foundation the IPAM allocator relies on.
     */
    public function up(): void
    {
        // A plain Schema ->change() can't convert text -> inet (Postgres needs an explicit USING
        // cast), so issue the ALTERs directly. The stored values are already valid IP strings.
        DB::statement('ALTER TABLE addresses ALTER COLUMN ip TYPE INET USING ip::inet');
        DB::statement('ALTER TABLE address_blocks ALTER COLUMN base_ip TYPE INET USING base_ip::inet');
        DB::statement('ALTER TABLE address_blocks ALTER COLUMN gateway TYPE INET USING gateway::inet');

        // Partial index backing the allocator's hot path:
        //   WHERE address_block_id = ? AND server_id IS NULL ORDER BY ip LIMIT n
        // Only free rows are indexed (so it shrinks as IPs get assigned), and because ip is now
        // stored in inet order the ORDER BY is served by the index with no sort. This is what
        // makes finding the next free address O(log N + n) instead of a table scan.
        DB::statement('CREATE INDEX addresses_free_by_block_ip_idx ON addresses (address_block_id, ip) WHERE server_id IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS addresses_free_by_block_ip_idx');

        // host() renders an inet back to its bare address string for the varchar columns.
        DB::statement('ALTER TABLE addresses ALTER COLUMN ip TYPE VARCHAR(255) USING host(ip)');
        DB::statement('ALTER TABLE address_blocks ALTER COLUMN base_ip TYPE VARCHAR(255) USING host(base_ip)');
        DB::statement('ALTER TABLE address_blocks ALTER COLUMN gateway TYPE VARCHAR(255) USING host(gateway)');
    }
};
