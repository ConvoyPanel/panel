<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IPLib\Factory as IPFactory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('address_pools', 'address_block_groups');
        Schema::rename('address_pool_to_node', 'address_block_group_to_network_interface');
        Schema::rename('ip_addresses', 'addresses');

        Schema::table('address_block_group_to_network_interface', function (Blueprint $table) {
            $table->renameColumn('address_pool_id', 'address_block_group_id');
            $table->unsignedBigInteger('network_interface_id')->nullable()->after('node_id');
            // Use a descriptive but shorter name for the foreign key constraint
            $table->foreign('network_interface_id', 'address_block_network_interface_foreign')
                ->references('id')
                ->on('network_interfaces')
                ->onDelete('cascade');
        });

        /**
         * Fill the network_interface_id column with the first network interface of the node.
         */
        DB::table('address_block_group_to_network_interface')
            ->select('address_block_group_to_network_interface.address_block_group_id',
                'address_block_group_to_network_interface.node_id',
                'network_interfaces.id as interface_id')
            ->join('network_interfaces', 'address_block_group_to_network_interface.node_id', '=', 'network_interfaces.node_id')
            ->whereNull('address_block_group_to_network_interface.network_interface_id')
            ->orderBy('network_interfaces.id')
            ->get()
            ->groupBy(function ($item) {
                return $item->address_block_group_id.'-'.$item->node_id;
            })
            ->each(function ($records) {
                $first = $records->first();
                if ($first && isset($first->interface_id)) {
                    DB::table('address_block_group_to_network_interface')
                        ->where('address_block_group_id', $first->address_block_group_id)
                        ->where('node_id', $first->node_id)
                        ->update(['network_interface_id' => $first->interface_id]);
                }
            });

        // Make network_interface_id non-nullable and remove node_id constraint
        Schema::table('address_block_group_to_network_interface', function (Blueprint $table) {
            // Make network_interface_id non-nullable
            $table->foreignId('network_interface_id')->nullable(false)->change();

            $table->dropForeign('address_pool_to_node_node_id_foreign');
            $table->dropColumn('node_id');
        });

        Schema::table('address_block_groups', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::create('address_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_block_group_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('version');
            $table->string('base_ip');
            $table->string('gateway')->nullable();
            $table->string('mac_address')->nullable();
            $table->integer('prefix_length_from');
            $table->integer('prefix_length_to');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('address_block_id')->after('address_pool_id')->nullable()->constrained()->cascadeOnDelete();
            $table->renameColumn('address', 'ip');
            $table->renameColumn('cidr', 'prefix_length');

            $table->unique(['address_block_id', 'ip']);
        });

        // Previously IPAM was this structure: IPs belong to Address Pools. Address Pools can be connected to nodes
        // Now we have a new structure: IPs belong to Address Blocks. Address Blocks belong to Address Block Groups. Address Block Groups can be connected to nodes.
        // So we need to migrate the data.
        // In each former address pool, organize the IPs into address blocks. Then, create a new address block group for each address pool.
        // Please refrain from using raw SQL so that its cross database compatibility is guaranteed. Use the DB facade instead.

        DB::transaction(callback: function () {
            // Define columns needed for selection and grouping
            $selectColumns = ['address_pool_id', 'gateway', 'prefix_length', 'type', 'mac_address', 'ip', 'id'];
            $groupByColumns = ['address_pool_id', 'gateway', 'prefix_length', 'type', 'mac_address'];

            // Fetch addresses and group them by a composite key
            $potentialBlocks = DB::table('addresses')
                ->select($selectColumns)
                ->whereNotNull('address_pool_id') // Only migrate addresses that belonged to a pool
                ->orderBy('id') // Consistent ordering helps grouping
                ->get()
                // Group by a generated composite key string instead of an array
                ->groupBy(function ($item) use ($groupByColumns) {
                    $keyParts = [];
                    foreach ($groupByColumns as $col) {
                        // Use 'NULL_VALUE' or similar for actual nulls to make key distinct
                        $keyParts[] = $item->{$col} ?? 'NULL_VALUE';
                    }

                    // Use a separator unlikely to appear in the data itself
                    return implode('||', $keyParts);
                });

            // Get all address block groups (formerly pools) to map IDs and get descriptions
            $addressBlockGroups = DB::table('address_block_groups')->get()->keyBy('id');

            /**
             * Iterate through the groups (key is the composite string, value is the collection)
             */
            foreach ($potentialBlocks as $compositeKey => $addressesInBlock) {
                /** @var Collection<int, stdClass> $addressesInBlock */
                // $addressesInBlock is now the final collection for this group
                $firstAddress = $addressesInBlock->first();
                if (! $firstAddress) {
                    continue;
                } // Skip if group is somehow empty

                // Now access properties directly from the first item in the group
                $oldPoolId = $firstAddress->address_pool_id;
                $gateway = $firstAddress->gateway ? IPFactory::parseAddressString($firstAddress->gateway)->toString() : null;
                $prefixLength = $firstAddress->prefix_length;
                $version = $firstAddress->type;
                $macAddress = $firstAddress->mac_address;
                $firstIp = $firstAddress->ip;

                $group = $addressBlockGroups->get($oldPoolId);
                if (! $group) {
                    throw new RuntimeException("Migration Error: Address Block Group (formerly Pool) with ID {$oldPoolId} not found during migration (composite key: {$compositeKey}). Cannot migrate addresses associated with it.");
                }

                // Create a new address_block record
                try {
                    // Attempt to parse the range using the correct Factory method
                    // Use the first IP and prefix length to define the subnet range
                    $range = IPFactory::parseRangeString($firstIp.'/'.$prefixLength);
                    if ($range) {
                        // Get the network address (start address of the range)
                        $baseIp = $range->getStartAddress()->toString();
                        $blockName = "Migrated Block ({$baseIp}/{$prefixLength})";
                    } else {
                        throw new RuntimeException("Migration Error: Failed to parse range for {$firstIp}/{$prefixLength} using ip-lib (composite key: {$compositeKey}).");
                    }
                } catch (Exception $e) {
                    throw new RuntimeException("Migration Error: Could not calculate base IP for {$firstIp}/{$prefixLength} during migration (composite key: {$compositeKey}): ".$e->getMessage());
                }

                $newBlockId = DB::table('address_blocks')->insertGetId([
                    'address_block_group_id' => $group->id,
                    'name' => $blockName,
                    'description' => $group->description,
                    'version' => $version,
                    'base_ip' => $baseIp,
                    'gateway' => $gateway,
                    'mac_address' => $macAddress,
                    'prefix_length_from' => $prefixLength,
                    'prefix_length_to' => $prefixLength,
                ]);

                // Update all addresses belonging to this specific group
                $addressIdsToUpdate = $addressesInBlock->pluck('id')->toArray();

                if (! empty($addressIdsToUpdate)) {
                    DB::table('addresses')
                        ->whereIn('id', $addressIdsToUpdate)
                        ->update(['address_block_id' => $newBlockId]);
                }
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            if (DB::table('addresses')->whereNull('address_block_id')->exists()) {
                throw new RuntimeException('Migration Error: Not all addresses could be assigned to an address block. Cannot make address_block_id non-nullable..');
            }

            $table->dropForeign('ip_addresses_address_pool_id_foreign'); // some shenanigans because Laravel can't predict the name as we fucked it all up.
            $table->dropColumn('address_pool_id');

            $table->foreignId('address_block_id')->nullable(false)->change();

            $table->dropColumn('type', 'gateway', 'mac_address', 'created_at', 'updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // --- Clear Addresses Data ---
        DB::table('addresses')->truncate();

        // --- Reverse Final Schema Cleanup ---
        Schema::table('addresses', function (Blueprint $table) {
            // Re-add columns (nullable for safety during rollback)
            $table->foreignId('address_pool_id')->after('id')->constrained('address_block_groups');
            $table->string('type')->nullable();
            $table->string('gateway')->nullable();
            $table->string('mac_address')->nullable();
            $table->timestamps(); // Re-add timestamps

            // Drop the new foreign key (constraint first, then column)
            $table->dropForeign(['address_block_id']);
            $table->dropColumn('address_block_id');

            // Rename columns back
            $table->renameColumn('ip', 'address');
            $table->renameColumn('prefix_length', 'cidr');
        });

        // --- Reverse New Table Creation ---
        Schema::dropIfExists('address_blocks');

        // --- Reverse Preparations ---
        Schema::table('address_block_groups', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Purge all records from address_block_group_to_network_interface
        DB::table('address_block_group_to_network_interface')->truncate();

        // Revert schema changes to address_block_group_to_network_interface
        Schema::table('address_block_group_to_network_interface', function (Blueprint $table) {
            // Drop network_interface_id column
            $table->dropConstrainedForeignId('network_interface_id');

            // Restore node_id foreign key
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');

            // Rename the column back
            $table->renameColumn('address_block_group_id', 'address_pool_id');
        });

        // --- Reverse Renames ---
        Schema::rename('addresses', 'ip_addresses');
        Schema::rename('address_block_group_to_network_interface', 'address_pool_to_node');
        Schema::rename('address_block_groups', 'address_pools');
    }
};
