<?php

namespace App\Enums\Node;

use Illuminate\Support\Str;

enum ConnectionErrorCode: string
{
    case TLS_ERROR = 'tls_error';
    case CONNECTION_REFUSED = 'connection_refused';
    case TIMEOUT = 'timeout';
    case DNS_ERROR = 'dns_error';
    case TOKEN_INVALID = 'token_invalid';
    case TOKEN_MISSING_PERMISSIONS = 'token_missing_permissions';
    case OTHER = 'other';

    private const MAPPINGS = [
        /** TLS errors */
        'certificate has expired' => self::TLS_ERROR,
        'TLS' => self::TLS_ERROR,
        'SSL' => self::TLS_ERROR,

        /** Broad network errors */
        'cURL error 28' => self::TIMEOUT,
        'hostname lookup' => self::DNS_ERROR,
        'Could not resolve host' => self::DNS_ERROR,
        'Connection refused' => self::CONNECTION_REFUSED,

        /** Token errors */
        'no such token' => self::TOKEN_INVALID,
        'invalid token value' => self::TOKEN_INVALID,
        'Permission check failed' => self::TOKEN_MISSING_PERMISSIONS,
    ];

    public static function classify(string $message): self
    {
        foreach (self::MAPPINGS as $needle => $errorType) {
            if (Str::contains($message, $needle)) {
                return $errorType;
            }
        }

        return self::OTHER;
    }
}
