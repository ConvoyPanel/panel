<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
        });

        // Migrate existing network values to the new network_interfaces table
        DB::transaction(function () {
            $nodes = DB::table('nodes')->whereNotNull('network')->get();

            foreach ($nodes as $node) {
                // Create a network interface with the network value from the node
                DB::table('network_interfaces')->insert([
                    'node_id' => $node->id,
                    'name' => $node->network,
                    'description' => 'Migrated from node settings',
                ]);
            }
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('network');
        });
    }

    public function down(): void
    {
        // First, get the first network interface for each node and store its name in the network field
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('network')->nullable()->after('memory_overallocate');
        });

        // Populate the network field with the first network interface name for each node
        DB::table('nodes')
            ->select('nodes.id as node_id', 'network_interfaces.name as interface_name')
            ->leftJoin('network_interfaces', 'nodes.id', '=', 'network_interfaces.node_id')
            ->orderBy('network_interfaces.id')
            ->get()
            ->groupBy('node_id')
            ->each(function ($nodes) {
                $first = $nodes->first();
                if ($first && isset($first->interface_name)) {
                    DB::table('nodes')
                        ->where('id', $first->node_id)
                        ->update(['network' => $first->interface_name]);
                }
            });

        // Make the network field non-nullable
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('network')->nullable(false)->change();
        });

        Schema::dropIfExists('network_interfaces');
    }
};
