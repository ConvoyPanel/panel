<?php

namespace Convoy\Exceptions;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DisplayException extends ConvoyException implements HttpExceptionInterface
{
    public const LEVEL_DEBUG = 'debug';

    public const LEVEL_INFO = 'info';

    public const LEVEL_WARNING = 'warning';

    public const LEVEL_ERROR = 'error';

    /**
     * DisplayException constructor.
     */
    public function __construct(string $message, ?Throwable $previous = null, protected string $level = self::LEVEL_ERROR, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorLevel(): string
    {
        return $this->level;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
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
        if ($request->expectsJson()) {
            return response()->json(Handler::toArray($this), $this->getStatusCode(), $this->getHeaders());
        }

        return redirect()->back()->withInput();
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
