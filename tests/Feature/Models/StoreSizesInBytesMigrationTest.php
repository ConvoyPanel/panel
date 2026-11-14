<?php

use App\Models\Backup;
use App\Models\Server;
use App\Models\Storage;
use Illuminate\Support\Facades\DB;

// Run backwards to get rows the way the panel stored them before, in whole
// mebibytes, and then forwards again.
function storeSizesInBytes(): object
{
    return require database_path('migrations/2026_11_14_000000_store_sizes_in_bytes.php');
}

function columnType(string $table, string $column): string
{
    return DB::selectOne(
        'SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
        [$table, $column],
    )->data_type;
}

it('converts mebibytes to bytes in bigint columns', function () {
    $server = Server::factory()->create([
        'memory' => 2048 * 1048576,
        'disk' => 40960 * 1048576,
        'bandwidth_usage' => 0,
        'bandwidth_limit' => -1,
    ]);
    $storage = Storage::factory()->create(['size' => 512000 * 1048576, 'reserved_bytes' => null]);
    $backup = Backup::factory()->create(['server_id' => $server->id, 'size' => 3 * 1048576]);

    storeSizesInBytes()->down();

    expect(DB::table('servers')->find($server->id))
        ->memory->toBe(2048)
        ->disk->toBe(40960)
        ->bandwidth_limit->toBe(-1)
        ->and(columnType('servers', 'memory'))->toBe('integer');

    // The largest a mebibyte column could hold no longer fits once it is bytes.
    DB::table('storages')->where('id', $storage->id)->update(['size' => 2147483647]);

    storeSizesInBytes()->up();

    expect(DB::table('servers')->find($server->id))
        ->memory->toBe(2048 * 1048576)
        ->disk->toBe(40960 * 1048576)
        ->bandwidth_usage->toBe(0)
        ->bandwidth_limit->toBe(-1)
        ->and(DB::table('storages')->find($storage->id))
        ->size->toBe(2147483647 * 1048576)
        ->reserved_bytes->toBeNull()
        ->and(DB::table('backups')->find($backup->id)->size)->toBe(3 * 1048576);

    foreach ([
        ['servers', 'memory'], ['servers', 'disk'], ['servers', 'bandwidth_usage'],
        ['servers', 'bandwidth_limit'], ['servers', 'backup_size_limit'], ['nodes', 'memory'],
        ['storages', 'size'], ['storages', 'reserved_bytes'], ['server_disks', 'size'],
        ['backups', 'size'], ['iso_library', 'size'], ['image_versions', 'size'],
    ] as [$table, $column]) {
        expect(columnType($table, $column))->toBe('bigint', "{$table}.{$column}");
    }
});

it('keeps exact byte counts that used to round down to whole mebibytes', function () {
    $backup = Backup::factory()->create(['size' => 4_233_187]);

    expect($backup->fresh()->size)->toBe(4_233_187)
        ->and($backup->getRawOriginal('size'))->toBe(4_233_187);
});
