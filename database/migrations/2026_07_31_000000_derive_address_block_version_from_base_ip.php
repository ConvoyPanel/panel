<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `version` was a hand-written copy of something base_ip already says. Since base_ip became a
     * native `inet` (see the 2026_07_07 conversion) Postgres can answer it exactly — family() —
     * so the stored copy bought nothing except room for the two to disagree: a block declaring
     * `ipv4` while holding an IPv6 base would take the v4 branch in every reservation, prefix and
     * stride calculation and quietly generate the wrong addresses.
     *
     * Replacing it with a STORED generated column keeps the column queryable (the admin filters
     * and Address::scopeWithIPv4/6 still say `where version = 'ipv4'`) and keeps the same
     * 'ipv4'/'ipv6' values on the wire, while making drift unrepresentable.
     */
    public function up(): void
    {
        // Nothing should be inconsistent — the request validators have always cross-checked base_ip
        // against the declared version — but if anything is, base_ip is the side the addresses were
        // actually generated from. Refuse to silently rewrite the block's version behind it.
        $mismatched = DB::table('address_blocks')
            ->whereRaw("version <> ('ipv' || family(base_ip)::text)")
            ->pluck('id');

        if ($mismatched->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot derive address_blocks.version from base_ip: block(s) '.$mismatched->implode(', ').
                ' declare a version that disagrees with their base IP. Correct the base IP (or delete the block) and re-run.'
            );
        }

        // Postgres has no ALTER COLUMN ... ADD GENERATED for stored columns, so the column has to be
        // dropped and re-added. Values are fully reproducible from base_ip, so nothing is lost.
        DB::statement('ALTER TABLE address_blocks DROP COLUMN version');
        DB::statement(<<<'SQL'
            ALTER TABLE address_blocks
            ADD COLUMN version VARCHAR(4)
            GENERATED ALWAYS AS ((CASE WHEN family(base_ip) = 4 THEN 'ipv4' ELSE 'ipv6' END)::varchar(4)) STORED
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE address_blocks DROP COLUMN version');
        DB::statement('ALTER TABLE address_blocks ADD COLUMN version VARCHAR(255)');
        DB::statement("UPDATE address_blocks SET version = 'ipv' || family(base_ip)::text");
        DB::statement('ALTER TABLE address_blocks ALTER COLUMN version SET NOT NULL');
    }
};
