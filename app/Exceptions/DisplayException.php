<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function collect;
use function config;
use function get_class;

class DisplayException extends ConvoyException implements HttpExceptionInterface
{
    public const LEVEL_DEBUG = 'debug';

    public const LEVEL_INFO = 'info';

    public const LEVEL_WARNING = 'warning';

    public const LEVEL_ERROR = 'error';

    /**
     * DisplayException constructor.
     */
    public function __construct(string $message, ?Throwable $previous = null, protected int $statusCode = Response::HTTP_BAD_REQUEST, protected string $level = self::LEVEL_ERROR, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorLevel(): string
    {
        return $this->level;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Render the exception to the user by adding a flashed message to the session
     * and then redirecting them back to the page that they came from. If the
     * request originated from an API hit, return the error in JSONAPI spec format.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        return response()->json($this->convertExceptionToArray($this), $this->getStatusCode(), $this->getHeaders());
    }

    /**
     * Convert the given exception to an array.
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        return config('app.debug') ? [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(fn ($trace) => Arr::except($trace, ['args']))->all(),
        ] : [
            'exception' => get_class($e),
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }

    /**
     * Determine if the given exception is an HTTP exception.
     */
    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpExceptionInterface;
    }

    /**
     * Log the exception to the logs using the defined error level only if the previous
     * exception is set.
     *
     * @throws Throwable
     */
    public function report()
    {
        if (! $this->getPrevious() instanceof Exception || ! Handler::isReportable($this->getPrevious())) {
            return null;
        }

        try {
            $logger = Container::getInstance()->make(LoggerInterface::class);
        } catch (Exception) {
            throw $this->getPrevious();
        }

        return $logger->{$this->getErrorLevel()}($this->getPrevious());
    }
}
