<?php

use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Support\Jobs\QueuedJobSignatures;

it('hands a job to a second worker only after the first has given up on it', function () {
    $retryAfter = config('queue.connections.redis.retry_after');
    $timeouts = ['Horizon default' => config('horizon.defaults.supervisor-1.timeout')];

    foreach (array_keys(QueuedJobSignatures::current()) as $class) {
        $timeout = (new ReflectionClass($class))->getDefaultProperties()['timeout'] ?? null;

        if (is_int($timeout) && $timeout > 0) {
            $timeouts[$class] = $timeout;
        }
    }

    // A job still running at retry_after is handed to a second worker. That copy finds the overlap
    // lock held, uses up its tries in seconds, and fails the deployment step the first copy is
    // still working on. The margin is the lock's, for the same reason: the kill can land late.
    $tooLong = array_filter(
        $timeouts,
        fn (int $timeout) => $timeout + ExpiringWithoutOverlapping::EXPIRY_MARGIN > $retryAfter,
    );

    expect($tooLong)->toBe([]);
});
