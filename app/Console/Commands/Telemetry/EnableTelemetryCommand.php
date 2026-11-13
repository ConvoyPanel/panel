<?php

namespace Convoy\Console\Commands\Telemetry;

use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Console\Command;

class EnableTelemetryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'c:telemetry:enable';

    /**
     * @var string
     */
    protected $description = 'Turn telemetry back on after `c:telemetry:disable`.';

    public function handle(TelemetryService $telemetry): int
    {
        if (! $telemetry->switchedOn()) {
            $telemetry->enable();
        }

        if ($variable = $telemetry->disabledBy()) {
            $this->warn("Telemetry is switched on, but {$variable} in .env keeps it off.");

            return Command::SUCCESS;
        }

        $this->info(sprintf(
            'Telemetry is on. The next report goes out at %s UTC; run `php artisan c:telemetry:show` to see it.',
            $telemetry->reportTime(),
        ));

        return Command::SUCCESS;
    }
}
