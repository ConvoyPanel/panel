<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stops storing what a storage holds twice.
 *
 * `storages.pve_content` is PVE's own content list, recorded by discovery. The
 * seven `stores_*` booleans beside it were a projection of that same string,
 * written at the same moment by the same code -- one fact in two places, which
 * is one place too many: adding the `import` type meant a migration, an enum
 * case, a model cast, two DTO fields, a controller mapping and a factory, any
 * of which could have disagreed with the others.
 *
 * The flags are now accessors over `pve_content`, and querying goes through
 * `Storage::scopeStores()`. Nothing outside the model changes shape: the API
 * still exposes `storesIso` and friends.
 *
 * Rows whose list was never recorded keep answering false, as they did before.
 */
return new class extends Migration
{
    private const FLAGS = [
        'stores_kvm' => 'images',
        'stores_lxc' => 'rootdir',
        'stores_lxc_templates' => 'vztmpl',
        'stores_backups' => 'backup',
        'stores_iso' => 'iso',
        'stores_snippets' => 'snippets',
        'stores_import' => 'import',
    ];

    public function up(): void
    {
        // Backfill first: a row registered by hand may carry flags an operator
        // set before discovery ever ran, and dropping the columns without
        // reading them would lose that.
        foreach (DB::table('storages')->whereNull('pve_content')->get() as $storage) {
            $content = collect(self::FLAGS)
                ->filter(fn (string $token, string $column) => (bool) ($storage->{$column} ?? false))
                ->values()
                ->implode(',');

            if ($content !== '') {
                DB::table('storages')->where('id', $storage->id)->update(['pve_content' => $content]);
            }
        }

        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::FLAGS));
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            foreach (array_keys(self::FLAGS) as $column) {
                $table->boolean($column)->default(false);
            }
        });

        foreach (self::FLAGS as $column => $token) {
            DB::table('storages')->whereRaw(
                "concat(',', coalesce(pve_content, ''), ',') like ?",
                ['%,'.$token.',%'],
            )->update([$column => true]);
        }
    }
};
