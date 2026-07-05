<?php

namespace Database\Seeders;

use App\Models\AddressBlockGroup;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds a representative spread of rows used ONLY by the pgloader cross-engine
 * migration harness (database/migration/verify.sh). It deliberately exercises
 * the column types most at risk in a MySQL -> Postgres conversion: tinyint(1)
 * booleans, bigint (node memory), timestamps, JSON, and UUIDs.
 *
 * Not part of the normal seed path — DatabaseSeeder does not call it.
 */
class PgloaderHarnessSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(20)->create();

        Location::factory()->count(5)->create()->each(function (Location $location) {
            Node::factory()->for($location)->count(3)->create();
        });

        Storage::factory()->count(12)->create();
        ISO::factory()->count(10)->create();
        AddressBlockGroup::factory()->count(6)->create();

        // Raw rows to guarantee JSON and UUID coverage regardless of factories.
        foreach (range(1, 8) as $i) {
            DB::table('activity_logs')->insert([
                'batch' => (string) Str::uuid(),
                'event' => 'harness.seed',
                'ip' => '203.0.113.'.$i,
                'properties' => json_encode([
                    'index' => $i,
                    'nested' => ['flag' => $i % 2 === 0, 'label' => "row-$i"],
                    'unicode' => 'café ☕ '.$i,
                ]),
                'timestamp' => now(),
            ]);
        }
    }
}
