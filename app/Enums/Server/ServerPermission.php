<?php

namespace App\Enums\Server;

use App\Policies\ServerPolicy;

/**
 * What a sub-user may do on one server.
 *
 * Every case maps to at least one route in `routes/api-client.php`; the table in
 * docs/permissions-design.md is the record of which. A permission with no endpoint behind it is
 * a promise the panel cannot keep, so the catalog is derived from the routes rather than from a
 * wishlist.
 *
 * Values are `<group>.<action>` and are a **stable API**: they are stored in
 * `server_subusers.permissions`, returned by the client API, and matched exhaustively by the
 * frontend copy map. Renaming one silently drops that grant from every row that carries it.
 *
 * The owner holds all of these implicitly and has no row. Managing sub-users is deliberately
 * absent: a permission to grant permissions is a permission to grant every permission.
 */
enum ServerPermission: string
{
    // Reading the server itself (`GET /servers/{server}`, `/state`, `/deployment`, `/addresses`,
    // `/resources`) carries no permission. A sub-user who cannot see the server they were given
    // is not a sub-user, and those endpoints expose nothing a share does not already imply.

    case ACTIVITY_READ = 'activity.read';
    case STATISTICS_READ = 'statistics.read';

    // One endpoint, four permissions: `POST /power` takes a PowerCommand, and the difference
    // between asking a guest to shut down and pulling its power is the whole point of splitting
    // them. See self::forPowerCommand().
    case POWER_START = 'power.start';
    case POWER_STOP = 'power.stop';
    case POWER_RESTART = 'power.restart';
    case POWER_KILL = 'power.kill';

    case CONSOLE_SESSION = 'console.session';
    case CONSOLE_CONFIGURE = 'console.configure';

    case BACKUP_READ = 'backup.read';
    case BACKUP_CREATE = 'backup.create';
    case BACKUP_RESTORE = 'backup.restore';
    case BACKUP_DELETE = 'backup.delete';

    case FIREWALL_READ = 'firewall.read';
    case FIREWALL_UPDATE = 'firewall.update';

    case SETTINGS_RENAME = 'settings.rename';
    case SETTINGS_BOOT_ORDER = 'settings.boot-order';
    case SETTINGS_MEDIA = 'settings.media';
    case SETTINGS_NETWORK = 'settings.network';
    case SETTINGS_AUTH = 'settings.auth';
    case SETTINGS_REINSTALL = 'settings.reinstall';

    /**
     * The policy ability each permission answers for.
     *
     * Ability names stay the camelCase ones the request classes already use, so the enum can be
     * introduced without rewriting every `authorize()`. {@see ServerPolicy} reads
     * this map in reverse.
     *
     * @return array<string, self>
     */
    public static function abilityMap(): array
    {
        return [
            'viewActivity' => self::ACTIVITY_READ,
            'viewStatistics' => self::STATISTICS_READ,
            'createConsoleSession' => self::CONSOLE_SESSION,
            'configureConsole' => self::CONSOLE_CONFIGURE,
            'viewBackups' => self::BACKUP_READ,
            'createBackup' => self::BACKUP_CREATE,
            'restoreBackup' => self::BACKUP_RESTORE,
            'deleteBackup' => self::BACKUP_DELETE,
            'viewFirewall' => self::FIREWALL_READ,
            'manageFirewall' => self::FIREWALL_UPDATE,
            'rename' => self::SETTINGS_RENAME,
            'updateBootOrder' => self::SETTINGS_BOOT_ORDER,
            'manageMedia' => self::SETTINGS_MEDIA,
            'updateNetworkSettings' => self::SETTINGS_NETWORK,
            'updateAuthSettings' => self::SETTINGS_AUTH,
            'reinstall' => self::SETTINGS_REINSTALL,
        ];
    }

    /**
     * The permission a power command needs.
     *
     * `reset` sits with `kill` and `suspend` with `shutdown` because what matters to the guest is
     * whether it is asked to stop or simply stopped.
     */
    public static function forPowerCommand(PowerCommand $command): self
    {
        return match ($command) {
            PowerCommand::START, PowerCommand::RESUME => self::POWER_START,
            PowerCommand::SHUTDOWN, PowerCommand::SUSPEND => self::POWER_STOP,
            PowerCommand::RESTART => self::POWER_RESTART,
            PowerCommand::KILL, PowerCommand::RESET => self::POWER_KILL,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
