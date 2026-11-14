<?php

namespace Convoy\Services\Telemetry;

use Carbon\CarbonImmutable;
use Convoy\Enums\Api\ApiKeyType;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\Coterm;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\PersonalAccessToken;
use Convoy\Models\SSHKey;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Builds the daily report (contract and key meanings: docs/telemetry.md).
 *
 * Only counts, switches and version numbers go in. No names, hostnames,
 * addresses, emails or credentials, and every version is cut down to its
 * digits here rather than trusting the console to do it. The admin "View
 * report" screen shows the output of this same builder, so what an operator
 * reads there is exactly what is sent.
 *
 * Building is read-only: it doesn't reset the view counters. The job does that
 * once the console has accepted the report.
 */
class TelemetryReportBuilder
{
    public const SCHEMA = 1;

    /** The console's caps (StoreTelemetryReportRequest). A value over them would get the whole report refused. */
    public const MAX_COUNT = 10_000_000;

    public const MAX_NODES = 500;

    private const MAX_NODE_SERVERS = 100_000;

    private const MAX_NODE_MEMORY_GIB = 1_000_000;

    /** Matches the console: a version starts with major.minor, and anything after the digits is dropped. */
    private const VERSION = '/^v?(\d+(?:\.\d+){1,3})/';

    /** A release, or "canary" for a source build. Anything a fork made up is sent as "other". */
    private const PANEL_VERSION = '/^v?\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)(?:\.\d+)?)?$/';

    public function __construct(
        private TelemetryService $telemetry,
        private ViewCounter $views,
    ) {}

    public function build(CarbonImmutable $now): TelemetryReport
    {
        $views = $this->views->peek();

        return new TelemetryReport(
            payload: [
                'schema' => self::SCHEMA,
                'installation' => $this->telemetry->installationId(),
                'panel' => $this->panel(),
                'users' => $this->users($now),
                'nodes' => $this->nodes(),
                'resources' => $this->resources(),
                'features' => $this->features(),
                'views' => array_map(fn (int $count) => min($count, self::MAX_COUNT), $views),
            ],
            views: $views,
        );
    }

    /** @return array{version: string, php: string, database: string, database_version: ?string} */
    private function panel(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        try {
            $serverVersion = $driver === 'sqlite'
                ? (string) $connection->selectOne('select sqlite_version() as version')->version
                : $connection->getServerVersion();
        } catch (Throwable) {
            $serverVersion = null;
        }

        // The mysql driver also talks to MariaDB, which only says so in its version string.
        if ($driver === 'mysql' && $serverVersion !== null && str_contains(strtolower($serverVersion), 'mariadb')) {
            $driver = 'mariadb';
        }

        $version = (string) config('app.version');

        return [
            'version' => $version === 'canary' || preg_match(self::PANEL_VERSION, $version) === 1 ? $version : 'other',
            'php' => self::version(PHP_VERSION) ?? '0.0',
            'database' => in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true) ? $driver : 'other',
            'database_version' => self::version($serverVersion),
        ];
    }

    /** @return array<string, mixed> */
    private function users(CarbonImmutable $now): array
    {
        $windows = ['day' => $now->subDay(), 'week' => $now->subWeek(), 'month' => $now->subDays(30)];

        $active = fn (bool $admins) => array_map(
            fn (CarbonImmutable $since) => self::count(User::query()
                ->when($admins, fn ($query) => $query->where('root_admin', true))
                ->where('last_active_at', '>', $since)
                ->count()),
            $windows,
        );

        return [
            'total' => self::count(User::query()->count()),
            'admins' => self::count(User::query()->where('root_admin', true)->count()),
            'active' => $active(false),
            'active_admins' => $active(true),
        ];
    }

    /** @return list<array{proxmox: null, agent: null, cpus: int, memory_gib: int, servers: int}> */
    private function nodes(): array
    {
        return Node::query()
            ->withCount('servers')
            ->orderBy('id')
            ->limit(self::MAX_NODES)
            ->get()
            ->map(fn (Node $node) => [
                // 4.x keeps no Proxmox version or agent on record, and asking Proxmox here would
                // make every report (and every "View report") wait on every node.
                'proxmox' => null,
                'agent' => null,
                // 4.x doesn't record a node's CPU count, only its memory and disk. 0 means unknown.
                'cpus' => 0,
                'memory_gib' => min((int) round(max(0, (int) $node->memory) / 1024 ** 3), self::MAX_NODE_MEMORY_GIB),
                'servers' => min((int) $node->servers_count, self::MAX_NODE_SERVERS),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    private function resources(): array
    {
        $addresses = fn (AddressType $type) => self::count(
            Address::query()->where('type', $type->value)->count()
        );
        $tokens = fn (ApiKeyType $type) => self::count(
            PersonalAccessToken::query()->where('type', $type->value)->count()
        );

        return [
            'address_pools' => self::count(AddressPool::query()->count()),
            'addresses.assigned' => self::count(Address::query()->whereNotNull('server_id')->count()),
            'addresses.ipv4' => $addresses(AddressType::IPV4),
            'addresses.ipv6' => $addresses(AddressType::IPV6),
            'api_tokens.account' => $tokens(ApiKeyType::ACCOUNT),
            'api_tokens.application' => $tokens(ApiKeyType::APPLICATION),
            'backups' => self::count(Backup::query()->count()),
            'coterms' => self::count(Coterm::query()->count()),
            'isos' => self::count(ISO::query()->count()),
            'locations' => self::count(Location::query()->count()),
            'ssh_keys' => self::count(SSHKey::query()->count()),
            'template_groups' => self::count(TemplateGroup::query()->count()),
            'templates' => self::count(Template::query()->count()),
        ];
    }

    /** @return array<string, bool> */
    private function features(): array
    {
        return [
            'activity.pruning' => (int) config('activity.prune_days') > 0,
            'backups.pruning' => (int) config('backups.prune_age') > 0,
            'backups.throttle' => (int) config('backups.throttles.period') > 0,
            'coterm' => Coterm::query()->exists(),
        ];
    }

    private static function count(int $count): int
    {
        return min(max(0, $count), self::MAX_COUNT);
    }

    private static function version(?string $value): ?string
    {
        return $value !== null && preg_match(self::VERSION, $value, $match) === 1 ? $match[1] : null;
    }
}
