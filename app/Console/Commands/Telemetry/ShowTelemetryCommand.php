<?php

namespace Convoy\Console\Commands\Telemetry;

use Convoy\Services\Telemetry\TelemetryReportService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Console\Command;

class ShowTelemetryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'c:telemetry:show';

    /**
     * @var string
     */
    protected $description = 'Print the anonymous report this panel sends the Convoy console once a day.';

    public function handle(TelemetryService $telemetry, TelemetryReportService $reports): int
    {
        if ($telemetry->enabled()) {
            $this->info(sprintf(
                'Telemetry is on. Once a day, at %s UTC, this panel sends the report below to %s.',
                $telemetry->reportTime(), $telemetry->url(),
            ));
            $this->line('It holds counts, switches and version numbers under a random installation id, and nothing that names a person or this panel.');
            $this->line('Turn it off in Admin → Settings → Telemetry, or with `php artisan c:telemetry:disable`.');
        } else {
            $this->warn('Telemetry is off, so nothing is sent. Were it on, this is the report the panel would send:');
        }

        $this->newLine();

        $this->line(json_encode(
            $reports->build($telemetry->installationId()),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));

        return Command::SUCCESS;
    }
}
