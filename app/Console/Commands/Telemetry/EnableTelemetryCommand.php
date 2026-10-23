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
        $telemetry->enable();

        if (! $telemetry->enabled()) {
            $this->warn('Telemetry is still off: CONVOY_TELEMETRY_ENABLED is false or CONVOY_TELEMETRY_URL is empty in .env.');

            return Command::SUCCESS;
        }

        $this->info(sprintf(
            'Telemetry is on. The next report goes out at %s %s; run `php artisan c:telemetry:show` to see it.',
            $telemetry->reportTime(), config('app.timezone'),
        ));

        return Command::SUCCESS;
    }
}
