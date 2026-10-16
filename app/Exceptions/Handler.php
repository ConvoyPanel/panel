<?php

namespace Convoy\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        // A missing route-bound model came back with Laravel's own message --
        // "No query results for model [Convoy\Models\Server]" -- which the panel
        // showed as-is. Name the record in plain words instead. Laravel has
        // already turned it into a NotFoundHttpException by the time this runs,
        // so the model is on the original exception.
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            $missing = $e->getPrevious();

            if (! $missing instanceof ModelNotFoundException || ! $request->expectsJson()) {
                return null;
            }

            $model = Str::of(class_basename($missing->getModel()))->headline()->lower();

            return response()->json([
                'message' => "This {$model} could not be found. It may have been deleted.",
            ], 404);
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
