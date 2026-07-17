<?php

namespace App\Exceptions\Http\Node;

use App\Enums\Node\Testing\ConnectionErrorCode;
use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

/**
 * Convoy could not read live state from a node.
 *
 * A node being unreachable is an expected condition, not a bug: the host may be
 * off, behind a firewall, or presenting a certificate we do not trust. What the
 * UI needs is *which* of those it is, so the error code carries the same
 * classification the connection test already reports
 * (`ConnectionErrorCode`), and the frontend maps it to the same copy.
 */
class NodeUnreachableException extends ServiceUnavailableHttpException implements HasErrorCode
{
    public function __construct(
        // Not `$code`: Exception already declares that property, and redeclaring
        // it readonly is a fatal error.
        private readonly ConnectionErrorCode $connectionError,
        string $message = 'Convoy could not reach this node.',
        ?Throwable $previous = null,
    ) {
        parent::__construct(retryAfter: null, message: $message, previous: $previous);
    }

    public function errorCode(): string
    {
        return $this->connectionError->value;
    }
}
