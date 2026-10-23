<?php

namespace Convoy\Console\Commands\Telemetry;

use Convoy\Services\Telemetry\PageViewCounter;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

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

    public function handle(TelemetryService $telemetry, PageViewCounter $views): int
    {
        $telemetry->disable();
        $views->flush();

        if ($telemetry->url() === '') {
            $this->info('Telemetry is off. CONVOY_TELEMETRY_URL is empty, so the console was not contacted.');

            return Command::SUCCESS;
        }

        try {
            $erased = $telemetry->erase();
        } catch (ConnectionException|RequestException $exception) {
            $this->warn('Telemetry is off, but the console could not be reached to erase this installation\'s reports: '.$exception->getMessage());
            $this->warn('The panel will ask again once a day until the console confirms.');

            return Command::FAILURE;
        }

        $this->info($erased
            ? 'Telemetry is off, and the console has erased this installation\'s reports.'
            : 'Telemetry is off. This panel had not sent a report, so there was nothing to erase.');

        return Command::SUCCESS;
    }
}
