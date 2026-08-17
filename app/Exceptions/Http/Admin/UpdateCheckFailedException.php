<?php

namespace App\Exceptions\Http\Admin;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

/**
 * The panel could not find out whether a newer release exists.
 *
 * Expected rather than exceptional — GitHub goes down, egress is filtered, a
 * rate limit trips. The scheduled check swallows this and keeps the last known
 * result; only the admin-triggered "check now" surfaces it, which is why it is
 * an HTTP exception at all.
 */
class UpdateCheckFailedException extends ServiceUnavailableHttpException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct(retryAfter: null, message: $message, previous: $previous);
    }
}
