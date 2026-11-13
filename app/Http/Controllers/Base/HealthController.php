<?php

namespace Convoy\Http\Controllers\Base;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Convoy\Http\Controllers\Controller;

/**
 * Answers the container healthcheck (HEALTHCHECK_PATH in the Dockerfile). It has
 * to fail when the panel cannot do its job, so it asks both of the services
 * every request depends on rather than only proving PHP is up.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => DB::connection()->select('select 1')),
            'redis' => $this->check('redis', fn () => Redis::connection()->ping()),
        ];

        $healthy = !in_array(false, $checks, true);

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'failing', 'checks' => $checks],
            $healthy ? 200 : 503,
        );
    }

    private function check(string $name, callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable $exception) {
            // One line, not a stack trace: the healthcheck runs every 30 seconds
            // and an outage would otherwise bury everything else in the log.
            Log::error("Health check: $name is unreachable.", ['error' => $exception->getMessage()]);

            return false;
        }
    }
}
