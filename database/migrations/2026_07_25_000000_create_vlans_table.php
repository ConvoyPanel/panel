<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vlans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_interface_id')
                ->constrained('network_interfaces')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('tag');
            $table->string('name')->nullable();
            $table->string('description')->nullable();

            // A tag is only meaningful within one bridge, so uniqueness is
            // scoped to the interface rather than global.
            $table->unique(['network_interface_id', 'tag']);
        });

        $this->backfill();
    }

    /**
     * Before this table existed a VLAN had no record — it was inferred from the
     * tags in use. Declare one row for every tag already resolvable today so
     * that existing trunks don't render as empty on first load:
     *
     *   - the bridge's own default tag, and
     *   - every distinct tag carried by a server sitting on that bridge.
     *
     * Only VLAN-aware interfaces are considered, matching the resolution in
     * ServerNetworkService: a non-aware bridge forces a null tag, so any tag
     * lingering on one is inert and must not be promoted into a declaration.
     */
    private function backfill(): void
    {
        $rows = DB::table('network_interfaces')
            ->where('is_vlan_aware', true)
            ->whereNotNull('vlan_tag')
            ->select('id as network_interface_id', 'vlan_tag as tag')
            ->union(
                DB::table('servers')
                    ->join(
                        'network_interfaces',
                        'servers.network_interface_id',
                        '=',
                        'network_interfaces.id',
                    )
                    ->where('network_interfaces.is_vlan_aware', true)
                    ->whereNotNull('servers.vlan_tag')
                    ->select(
                        'network_interfaces.id as network_interface_id',
                        'servers.vlan_tag as tag',
                    )
                    ->distinct(),
            )
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        // `union` de-duplicates across the two halves, but a bridge default that
        // a server also carries explicitly can still arrive twice from the same
        // half on some drivers — key the insert to be certain.
        DB::table('vlans')->insert(
            $rows
                ->keyBy(fn ($row) => "{$row->network_interface_id}:{$row->tag}")
                ->map(fn ($row) => [
                    'network_interface_id' => $row->network_interface_id,
                    'tag' => $row->tag,
                    'name' => null,
                    'description' => null,
                ])
                ->values()
                ->all(),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vlans');
    }
};
