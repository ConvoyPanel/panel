<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Services\Anchor\AnchorMigrationClient;
use Illuminate\Database\Seeder;
use Throwable;

/** Throwaway: run a real export and poll it to completion. */
class TmpAnchorProbeSeeder extends Seeder
{
    public function run(): void
    {
        $client = app(AnchorMigrationClient::class);
        $node = Node::find(3);

        try {
            $job = $client->export($node, 9200);
        } catch (Throwable $e) {
            $this->command->error('export FAILED: '.get_class($e).': '.$e->getMessage());

            return;
        }

        $this->command->info("export job {$job->id} started");

        for ($i = 0; $i < 60; $i++) {
            sleep(5);

            try {
                $job = $client->exportStatus($node, $job->id);
            } catch (Throwable $e) {
                $this->command->error('  status FAILED: '.$e->getMessage());

                return;
            }

            $this->command->info(sprintf(
                '  [%02d] status=%s progress=%s%%%s',
                $i,
                $job->status->value,
                $job->progress ?? 0,
                $job->error ? ' error='.$job->error : '',
            ));

            if (in_array($job->status->value, ['completed', 'failed', 'cancelled'], true)) {
                $this->command->info('  artifact='.($job->artifact ?? 'null')
                    .' sha256='.substr((string) $job->sha256, 0, 16)
                    .' size='.($job->size ?? 'null'));

                return;
            }
        }

        $this->command->warn('  gave up waiting');
    }
}
