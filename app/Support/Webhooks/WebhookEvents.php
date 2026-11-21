<?php

namespace App\Support\Webhooks;

use App\Enums\Audit\AuditEvent;
use Illuminate\Support\Str;

/**
 * What a webhook can subscribe to.
 *
 * Every audit event, under its audit name, so anything the audit log records an integration can
 * hear about. Plus the outcomes of work that finishes later, which the audit log does not record
 * because nobody did them: an install or a migration finishing, a backup landing. These are the
 * ones an integration most wants, since the request that started them only says "started".
 *
 * A subscription is a list of names, or patterns ending in `*` (`server.*`, or `*` for all).
 */
final class WebhookEvents
{
    public const SERVER_INSTALLATION_COMPLETED = 'server.installation.completed';

    public const SERVER_INSTALLATION_FAILED = 'server.installation.failed';

    public const SERVER_MIGRATION_COMPLETED = 'server.migration.completed';

    public const SERVER_MIGRATION_FAILED = 'server.migration.failed';

    public const SERVER_BACKUP_COMPLETED = 'server.backup.completed';

    public const SERVER_BACKUP_FAILED = 'server.backup.failed';

    /** Sent by the test button, and only to the webhook being tested. */
    public const PING = 'webhook.ping';

    /** @return list<string> */
    public static function lifecycle(): array
    {
        return [
            self::SERVER_INSTALLATION_COMPLETED,
            self::SERVER_INSTALLATION_FAILED,
            self::SERVER_MIGRATION_COMPLETED,
            self::SERVER_MIGRATION_FAILED,
            self::SERVER_BACKUP_COMPLETED,
            self::SERVER_BACKUP_FAILED,
        ];
    }

    /** @return list<string> Every event a webhook can subscribe to. */
    public static function all(): array
    {
        return [
            ...self::lifecycle(),
            ...array_map(fn (AuditEvent $event) => $event->value, AuditEvent::cases()),
        ];
    }

    /** Whether a subscription entry names a real event, or a pattern that matches at least one. */
    public static function isValidSubscription(string $entry): bool
    {
        if ($entry === '*') {
            return true;
        }

        if (str_ends_with($entry, '*')) {
            return collect(self::all())->contains(fn (string $event) => Str::is($entry, $event));
        }

        return in_array($entry, self::all(), true);
    }

    /** @param  list<string>  $subscribed */
    public static function matches(array $subscribed, string $event): bool
    {
        foreach ($subscribed as $entry) {
            if ($entry === $event || (str_ends_with($entry, '*') && Str::is($entry, $event))) {
                return true;
            }
        }

        return false;
    }
}
