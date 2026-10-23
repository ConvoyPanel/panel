<?php

namespace Convoy\Services\Telemetry;

use Carbon\Carbon;
use Convoy\Enums\Api\ApiKeyType;
use Convoy\Enums\Server\Status;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\Coterm;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\PersonalAccessToken;
use Convoy\Models\Server;
use Convoy\Models\SSHKey;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Convoy\Repositories\Proxmox\Node\ProxmoxNodeRepository;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the daily telemetry report (schema 1, as the console's
 * POST /api/v1/telemetry accepts it).
 *
 * Everything in it is a count, a switch or a version number. It never carries
 * a name, email, hostname, address, APP_URL or license key: the only string a
 * panel picks is the installation id, and the console only keeps the version
 * numbers it can parse.
 */
class TelemetryReportService
{
    public const SCHEMA = 1;

    private const BYTES_PER_GIBIBYTE = 1073741824;

    /** How long to wait on each node for its CPU count and Proxmox version. */
    private const NODE_TIMEOUT_SECONDS = 5;

    /**
     * Asking nodes stops after this long, so a fleet of unreachable nodes can't
     * hold the job past its time limit. The rest are reported without the
     * details only Proxmox knows.
     */
    private const NODE_LOOKUP_SECONDS = 60;

    public function __construct(private ProxmoxNodeRepository $repository) {}

    public function build(string $installation): array
    {
        return [
            'schema' => self::SCHEMA,
            'installation' => $installation,
            'panel' => $this->panel(),
            'users' => $this->users(),
            'nodes' => $this->nodes(),
            'resources' => $this->resources(),
            'features' => $this->features(),
        ];
    }

    private function panel(): array
    {
        $connection = DB::connection();
        $database = $connection->getDriverName();

        // MariaDB runs on Laravel's mysql driver.
        if ($connection instanceof MySqlConnection && rescue(fn () => $connection->isMaria(), false, false)) {
            $database = 'mariadb';
        }

        return [
            'version' => (string) config('app.version'),
            'php' => self::version(PHP_VERSION),
            'database' => $database,
            'database_version' => rescue(
                fn () => self::version($connection->getServerVersion()), null, false,
            ),
        ];
    }

    /**
     * Accounts, and how many of them made a signed-in request in the last day,
     * week and 30 days (users.last_active_at).
     */
    private function users(): array
    {
        $windows = [
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subDays(30),
        ];

        $active = fn (bool $admins) => array_map(
            fn (Carbon $since) => User::query()
                ->when($admins, fn ($query) => $query->where('root_admin', true))
                ->where('last_active_at', '>=', $since)
                ->count(),
            $windows,
        );

        return [
            'total' => User::query()->count(),
            'admins' => User::query()->where('root_admin', true)->count(),
            'active' => $active(false),
            'active_admins' => $active(true),
        ];
    }

    /**
     * One entry per node. The panel doesn't store a node's CPU count or Proxmox
     * version, so those come from asking the node; one that doesn't answer is
     * sent with no version and 0 CPUs. Memory is the node's memory as entered in
     * the panel. 4.x has no node agent, so `agent` is always null.
     */
    private function nodes(): array
    {
        $deadline = microtime(true) + self::NODE_LOOKUP_SECONDS;

        return Node::query()
            ->withCount('servers')
            ->orderBy('id')
            ->get()
            ->map(function (Node $node) use ($deadline) {
                $status = microtime(true) < $deadline ? $this->status($node) : [];

                return [
                    'proxmox' => self::version(
                        Str::after((string) ($status['pveversion'] ?? ''), 'pve-manager/'),
                    ),
                    'agent' => null,
                    'cpus' => (int) ($status['cpuinfo']['cpus'] ?? 0),
                    'memory_gib' => (int) round($node->memory / self::BYTES_PER_GIBIBYTE),
                    'servers' => (int) $node->servers_count,
                ];
            })
            ->all();
    }

    private function status(Node $node): array
    {
        try {
            return $this->repository->setNode($node)->getStatus([
                'timeout' => self::NODE_TIMEOUT_SECONDS,
                'connect_timeout' => self::NODE_TIMEOUT_SECONDS,
            ]);
        } catch (Throwable) {
            // The details are a nice-to-have; a node that can't answer, for
            // whatever reason (including an answer that isn't JSON), must not
            // cost the rest of the report.
            return [];
        }
    }

    private function resources(): array
    {
        return [
            'locations' => Location::query()->count(),
            'nodes.without_tls_verification' => Node::query()->where('verify_tls', false)->count(),
            'servers.suspended' => Server::query()->where('status', Status::SUSPENDED->value)->count(),
            'servers.install_failed' => Server::query()->where('status', Status::INSTALL_FAILED->value)->count(),
            'backups' => Backup::query()->count(),
            'isos' => ISO::query()->count(),
            'template_groups' => TemplateGroup::query()->count(),
            'templates' => Template::query()->count(),
            'address_pools' => AddressPool::query()->count(),
            'addresses.ipv4' => Address::query()->where('type', 'ipv4')->count(),
            'addresses.ipv6' => Address::query()->where('type', 'ipv6')->count(),
            'coterms' => Coterm::query()->count(),
            'ssh_keys' => SSHKey::query()->count(),
            'api_tokens.account' => PersonalAccessToken::query()->where('type', ApiKeyType::ACCOUNT)->count(),
            'api_tokens.application' => PersonalAccessToken::query()->where('type', ApiKeyType::APPLICATION)->count(),
        ];
    }

    private function features(): array
    {
        return [
            'coterm' => Node::query()->whereNotNull('coterm_id')->exists(),
        ];
    }

    /**
     * The leading major.minor[.patch] of a version string, so whatever follows
     * it (a distro build, MariaDB's suffix, a commit) never leaves the panel.
     */
    private static function version(?string $value): ?string
    {
        return ! is_null($value) && preg_match('/^v?(\d+(?:\.\d+){1,3})/', trim($value), $match) === 1
            ? $match[1]
            : null;
    }
}
