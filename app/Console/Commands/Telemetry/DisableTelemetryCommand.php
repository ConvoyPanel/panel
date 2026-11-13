<?php

namespace Convoy\Console\Commands\Telemetry;

use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Console\Command;

class DisableTelemetryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'c:telemetry:disable';

    /**
     * @var string
     */
    protected $description = 'Stop sending telemetry, and erase this installation\'s reports from the Convoy console.';

    public function handle(TelemetryService $telemetry): int
    {
        if (! $telemetry->switchedOn()) {
            $this->info('Telemetry is already off.');

            return Command::SUCCESS;
        }

        if (is_null($telemetry->disable())) {
            $this->info('Telemetry is off. CONVOY_TELEMETRY_URL is empty, so the console was not contacted.');

            return Command::SUCCESS;
        }

        $this->info('Telemetry is off. The console has been asked to erase this installation\'s reports; the queue retries for about seven hours if it can\'t be reached.');

        return Command::SUCCESS;
    }
}
