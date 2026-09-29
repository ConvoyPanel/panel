<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the build time the catalogue publishes, and stops inventing a version.
 *
 * The catalogue numbers nothing, so the import derived a version from the build
 * date: `2026.9.4`. Two builds of one recipe on the same day then collided, and
 * the collision was resolved by incrementing the patch -- which produced
 * `2026.9.5` for something built on the 4th, and stole the number a genuine
 * build on the 5th would want, so the drift compounded.
 *
 * The identity was there all along. `built_at` is a second-resolution timestamp
 * and is unique per build; truncating it to a date is what manufactured the
 * collision. So the timestamp is stored as itself, and the version goes back to
 * being what it is for a catalogue image: which build this is, counting up.
 *
 * Existing registry rows are renumbered in the order they already sort in, so
 * nothing changes position. `built_at` is left null for them rather than guessed
 * back out of the old label: the collided ones encode the wrong day, and that is
 * the whole defect being removed. A later re-import fills it in from the
 * catalogue, because the import backfills the row it finds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_versions', function (Blueprint $table) {
            $table->timestamp('built_at')->nullable()->after('source');
        });

        $definitions = DB::table('image_versions')
            ->where('source', 'registry')
            ->distinct()
            ->pluck('image_definition_id');

        foreach ($definitions as $definitionId) {
            $rows = DB::table('image_versions')
                ->where('image_definition_id', $definitionId)
                ->where('source', 'registry')
                ->orderBy('version_major')
                ->orderBy('version_minor')
                ->orderBy('version_patch')
                ->pluck('id');

            foreach ($rows as $index => $id) {
                $build = $index + 1;

                DB::table('image_versions')->where('id', $id)->update([
                    'version' => (string) $build,
                    'version_major' => $build,
                    'version_minor' => 0,
                    'version_patch' => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('image_versions', function (Blueprint $table) {
            $table->dropColumn('built_at');
        });

        // The date-derived labels are not reconstructable: the build dates they
        // encoded were never stored anywhere else, which is why they are being
        // replaced. The build numbers stay.
    }
};
