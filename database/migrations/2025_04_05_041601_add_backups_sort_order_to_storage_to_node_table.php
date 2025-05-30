<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->integer('backup_order')->nullable()->after('node_id');
        });

        // automatically add the backup_order column to existing records that can store backups. make sure to scope by node
        DB::transaction(function () {
            // Get all nodes to scope by node
            $nodes = DB::table('nodes')->get(['id']);

            foreach ($nodes as $node) {
                // Get all storage records for this node that can store backups
                $storages = DB::table('storage_to_node')
                    ->select('storage_to_node.storage_id', 'storage_to_node.node_id')
                    ->join('storages', 'storage_to_node.storage_id', '=', 'storages.id')
                    ->where('storage_to_node.node_id', $node->id)
                    ->where('storages.stores_backups', true)  // Using the new column name from the previous migration
                    ->orderBy('storages.id')  // Order by ID to provide consistent results
                    ->get();

                // Assign incrementing backup_order to each storage
                $order = 1;
                foreach ($storages as $storage) {
                    DB::table('storage_to_node')
                        ->where('storage_id', $storage->storage_id)
                        ->where('node_id', $storage->node_id)
                        ->update(['backup_order' => $order]);

                    $order++;
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('storage_to_node', function (Blueprint $table) {
            $table->dropColumn('backup_order');
        });
    }
};
