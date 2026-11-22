<?php

namespace App\Data\Audit;

use App\Models\AuditLog;
use Spatie\LaravelData\Data;

/**
 * The API token an action came in over. Several integrations can share the System actor, so this
 * is what tells their actions apart.
 */
class AuditTokenData extends Data
{
    public function __construct(
        /** Null once the token has been revoked or deleted. */
        public ?int $id,
        /** The token's name when the action happened. */
        public string $name,
    ) {}

    public static function fromLog(AuditLog $log): ?self
    {
        if ($log->api_token_id === null && $log->api_token_name === null) {
            return null;
        }

        return new self(
            id: $log->api_token_id,
            name: $log->api_token_name ?? 'Token #'.$log->api_token_id,
        );
    }
}
