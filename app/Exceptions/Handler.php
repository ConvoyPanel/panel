<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Return an array of exceptions that should not be reported.
     */
    public static function isReportable(Exception $exception): bool
    {
        return (new static(Container::getInstance()))->shouldReport($exception);
    }

    /**
     * Helper method to allow reaching into the handler to convert an exception
     * into the expected array response type.
     */
    public static function toArray(Throwable $e): array
    {
        return (new self(app()))->convertExceptionToArray($e);
    }
}
