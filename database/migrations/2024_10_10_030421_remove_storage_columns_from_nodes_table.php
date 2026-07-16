<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('server_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('iso_library', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('uuid')->nullable()->constrained()->onDelete('cascade');
            $table->dropColumn('updated_at');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('node_id')->nullable()->constrained()->onDelete('cascade');
            $table->dropColumn('updated_at');
        });

        // First, create storage entries using the existing node information
        DB::transaction(function () {
            $nodes = DB::table('nodes')->get();
            $storageIdsByNode = [];
            $vmStorageIdsByNode = [];
            $isoStorageIdsByNode = [];

            foreach ($nodes as $node) {
                // Create a map to track unique storage paths and their IDs
                $storagePathMap = [];

                // Process VM storage
                $vmPath = $node->vm_storage;
                if (! isset($storagePathMap[$vmPath])) {
                    $vmStorageId = DB::table('storages')->insertGetId([
                        'nickname' => 'VM Storage',
                        'description' => 'Migrated from node settings',
                        'name' => $vmPath,
                        'size' => $node->disk,
                        'is_shareable' => false,
                        'has_kvm' => true,
                        'has_lxc' => false,
                        'has_lxc_templates' => false,
                        'has_backups' => false,
                        'has_iso' => false,
                        'has_snippets' => false,
                    ]);

                    $storagePathMap[$vmPath] = [
                        'id' => $vmStorageId,
                        'has_kvm' => true,
                        'has_backups' => false,
                        'has_iso' => false,
                    ];

                    // Store VM storage ID for this node to use with servers later
                    $vmStorageIdsByNode[$node->id] = $vmStorageId;
                } else {
                    // Update the existing storage to add VM capability
                    DB::table('storages')
                        ->where('id', $storagePathMap[$vmPath]['id'])
                        ->update(['has_kvm' => true]);
                    $storagePathMap[$vmPath]['has_kvm'] = true;

                    // Store VM storage ID for this node to use with servers later
                    $vmStorageIdsByNode[$node->id] = $storagePathMap[$vmPath]['id'];
                }

                // Process Backup storage
                $backupPath = $node->backup_storage;
                if (! isset($storagePathMap[$backupPath])) {
                    $backupStorageId = DB::table('storages')->insertGetId([
                        'nickname' => 'Backup Storage',
                        'description' => 'Migrated from node settings',
                        'name' => $backupPath,
                        'size' => $node->disk,
                        'is_shareable' => false,
                        'has_kvm' => false,
                        'has_lxc' => false,
                        'has_lxc_templates' => false,
                        'has_backups' => true,
                        'has_iso' => false,
                        'has_snippets' => false,
                    ]);

                    $storagePathMap[$backupPath] = [
                        'id' => $backupStorageId,
                        'has_kvm' => false,
                        'has_backups' => true,
                        'has_iso' => false,
                    ];

                    // Store backup storage ID for this node to use with backups later
                    $storageIdsByNode[$node->id] = $backupStorageId;
                } else {
                    // Update the existing storage to add Backup capability
                    DB::table('storages')
                        ->where('id', $storagePathMap[$backupPath]['id'])
                        ->update(['has_backups' => true]);
                    $storagePathMap[$backupPath]['has_backups'] = true;

                    // Store backup storage ID for this node to use with backups later
                    $storageIdsByNode[$node->id] = $storagePathMap[$backupPath]['id'];
                }

                // Process ISO storage
                $isoPath = $node->iso_storage;
                if (! isset($storagePathMap[$isoPath])) {
                    $isoStorageId = DB::table('storages')->insertGetId([
                        'nickname' => 'ISO Storage',
                        'description' => 'Migrated from node settings',
                        'name' => $isoPath,
                        'size' => $node->disk,
                        'is_shareable' => false,
                        'has_kvm' => false,
                        'has_lxc' => false,
                        'has_lxc_templates' => false,
                        'has_backups' => false,
                        'has_iso' => true,
                        'has_snippets' => false,
                    ]);

                    $storagePathMap[$isoPath] = [
                        'id' => $isoStorageId,
                        'has_kvm' => false,
                        'has_backups' => false,
                        'has_iso' => true,
                    ];

                    // Store ISO storage ID for this node to use with ISO library later
                    $isoStorageIdsByNode[$node->id] = $isoStorageId;
                } else {
                    // Update the existing storage to add ISO capability
                    DB::table('storages')
                        ->where('id', $storagePathMap[$isoPath]['id'])
                        ->update(['has_iso' => true]);
                    $storagePathMap[$isoPath]['has_iso'] = true;

                    // Store ISO storage ID for this node to use with ISO library later
                    $isoStorageIdsByNode[$node->id] = $storagePathMap[$isoPath]['id'];
                }

                // Update nicknames based on combined capabilities
                foreach ($storagePathMap as $path => $storage) {
                    $capabilities = [];
                    if ($storage['has_kvm']) {
                        $capabilities[] = 'VM';
                    }
                    if ($storage['has_backups']) {
                        $capabilities[] = 'Backup';
                    }
                    if ($storage['has_iso']) {
                        $capabilities[] = 'ISO';
                    }

                    $nickname = implode('/', $capabilities).' Storage';

                    DB::table('storages')
                        ->where('id', $storage['id'])
                        ->update(['nickname' => $nickname]);

                    // Link storage to the node
                    DB::table('storage_to_node')->insert([
                        'storage_id' => $storage['id'],
                        'node_id' => $node->id,
                    ]);
                }
            }

            // Now update all backups to use the appropriate storage_id
            $servers = DB::table('servers')->get();
            foreach ($servers as $server) {
                if (isset($storageIdsByNode[$server->node_id])) {
                    DB::table('backups')
                        ->where('server_id', $server->id)
                        ->update(['storage_id' => $storageIdsByNode[$server->node_id]]);
                }

                // Update the server's storage_id to use the VM storage
                if (isset($vmStorageIdsByNode[$server->node_id])) {
                    DB::table('servers')
                        ->where('id', $server->id)
                        ->update(['storage_id' => $vmStorageIdsByNode[$server->node_id]]);
                }
            }

            // Update ISO library items to use the appropriate ISO storage
            // ISO library doesn't have server_id, it directly has node_id
            $isoLibraryItems = DB::table('iso_library')
                ->whereNotNull('node_id')
                ->get();

            foreach ($isoLibraryItems as $iso) {
                if (isset($isoStorageIdsByNode[$iso->node_id])) {
                    DB::table('iso_library')
                        ->where('id', $iso->id)
                        ->update(['storage_id' => $isoStorageIdsByNode[$iso->node_id]]);
                }
            }
        });

        // Now drop the columns that are no longer needed
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn([
                'disk',
                'disk_overallocate',
                'vm_storage',
                'backup_storage',
                'iso_storage',
            ]);
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_id')->nullable(false)->change();
        });

        Schema::table('iso_library', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_id')->nullable(false)->change();
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_id');
        });

        // DANGER: This will delete all ISO library entries, which may not be desired.
        DB::table('iso_library')->truncate();
        Schema::table('iso_library', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
            $table->foreignId('node_id')->after('uuid')->constrained()->onDelete('cascade');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->after('memory_overallocate', function (Blueprint $table) {
                $table->integer('disk')->nullable()->unsigned();
                $table->integer('disk_overallocate')->default(0);
                $table->string('vm_storage')->nullable();
                $table->string('backup_storage')->nullable();
                $table->string('iso_storage')->nullable();
            });
        });

        // Restore the original storage path values from the storages table
        DB::transaction(function () {
            $nodes = DB::table('nodes')->get();

            foreach ($nodes as $node) {
                // Find storages linked to this node
                $nodeStorages = DB::table('storage_to_node')
                    ->where('node_id', $node->id)
                    ->join('storages', 'storage_to_node.storage_id', '=', 'storages.id')
                    ->select('storages.*')
                    ->get();

                // Find storages by capability
                $vmStorage = $nodeStorages->firstWhere('has_kvm', true);
                $backupStorage = $nodeStorages->firstWhere('has_backups', true);
                $isoStorage = $nodeStorages->firstWhere('has_iso', true);

                // Default size from the first storage
                $diskSize = $nodeStorages->first() ? $nodeStorages->first()->size : 0;

                // Update the node with values from the storages
                DB::table('nodes')
                    ->where('id', $node->id)
                    ->update([
                        'disk' => $diskSize,
                        'disk_overallocate' => 0, // Default value
                        'vm_storage' => $vmStorage ? $vmStorage->name : 'NOT_DETECTED',
                        'backup_storage' => $backupStorage ? $backupStorage->name : 'NOT_DETECTED',
                        'iso_storage' => $isoStorage ? $isoStorage->name : 'NOT_DETECTED',
                    ]);
            }
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('disk')->unsigned()->nullable(false)->change();
            $table->string('vm_storage')->nullable(false)->change();
            $table->string('backup_storage')->nullable(false)->change();
            $table->string('iso_storage')->nullable(false)->change();
        });
    }
};
