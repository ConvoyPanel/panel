<?php

namespace App\Services\Telemetry;

use App\Enums\Api\ApiKeyType;
use App\Enums\Audit\AuditEvent;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Cluster;
use App\Models\ImageDefinition;
use App\Models\ImageVersion;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Passkey;
use App\Models\PersonalAccessToken;
use App\Models\Relay;
use App\Models\ServerPreset;
use App\Models\SSHKey;
use App\Models\Storage;
use App\Models\User;
use App\Models\UserInvite;
use App\Models\Vlan;
use App\Services\Auth\OAuthAuthenticationService;
use App\Services\Mail\MailConfigurator;
use App\Settings\AccountSettings;
use App\Settings\AnchorSettings;
use App\Settings\AuditSettings;
use App\Settings\BandwidthSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Builds the daily report (contract and key meanings: docs/telemetry.md).
 *
 * Only counts, switches and version numbers go in. No names, hostnames,
 * addresses, emails or license data, and every version is cut down to its
 * digits here rather than trusting the console to do it. The admin "View
 * report" screen shows the output of this same builder, so what an operator
 * reads there is exactly what is sent.
 *
 * Building is read-only: it neither resets the view counters nor moves the
 * activity window. The job does that once the console has accepted the report.
 */
class TelemetryReportBuilder
{
    public const SCHEMA = 1;

    /** Matches the console: a version starts with major.minor, and anything after the digits is dropped. */
    private const VERSION = '/^v?(\d+(?:\.\d+){1,3})/';

    /** A release, or "canary" for a source build. Anything a fork made up is sent as "other". */
    private const PANEL_VERSION = '/^v?\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)(?:\.\d+)?)?$/';

    /** The OAuth drivers the panel ships. A provider an operator added to the config is not reported by name. */
    private const OAUTH_PROVIDERS = ['google', 'github', 'gitlab', 'oidc'];

    public function __construct(
        private TelemetryService $telemetry,
        private ViewCounter $views,
        private OAuthAuthenticationService $oauth,
        private MailConfigurator $mail,
        private AccountSettings $accounts,
        private AnchorSettings $anchor,
        private AuditSettings $audit,
        private BandwidthSettings $bandwidth,
    ) {}

    public function build(CarbonImmutable $until): TelemetryReport
    {
        $views = $this->views->peek();

        return new TelemetryReport(
            payload: [
                'schema' => self::SCHEMA,
                'installation' => $this->telemetry->installationId(),
                'panel' => $this->panel(),
                'users' => $this->users($until),
                'nodes' => $this->nodes(),
                'resources' => $this->resources($until),
                'features' => $this->features(),
                'activity' => $this->activity($this->telemetry->windowStart($until), $until),
                'views' => $views,
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
    private function users(CarbonImmutable $until): array
    {
        $windows = ['day' => $until->subDay(), 'week' => $until->subWeek(), 'month' => $until->subDays(30)];

        $active = fn (bool $admins) => array_map(
            fn (CarbonImmutable $since) => User::query()
                ->when($admins, fn ($query) => $query->where('root_admin', true))
                ->where('last_active_at', '>', $since)
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

    /** @return list<array{proxmox: ?string, agent: ?string, cpus: int, memory_gib: int, servers: int}> */
    private function nodes(): array
    {
        return Node::query()
            ->withCount('servers')
            ->orderBy('id')
            ->limit(1000)
            ->get()
            ->map(fn (Node $node) => [
                // What the host reported when its Anchor enrolled. A node added without an Anchor
                // has no version on record, and asking Proxmox here would make every report (and
                // every "View report") wait on every node.
                'proxmox' => self::version($node->agent_reported_facts['pve_version'] ?? null),
                'agent' => self::version($node->agent_version),
                'cpus' => max(0, $node->cpu_count),
                'memory_gib' => (int) round(max(0, $node->memory) / 1024 ** 3),
                'servers' => (int) $node->servers_count,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    private function resources(CarbonImmutable $until): array
    {
        $addresses = fn (AddressVersion $version) => Address::query()
            ->whereHas('addressBlock', fn ($query) => $query->where('version', $version->value))
            ->count();

        return [
            'address_blocks' => AddressBlock::query()->count(),
            'addresses.assigned' => Address::query()->whereNotNull('server_id')->count(),
            'addresses.ipv4' => $addresses(AddressVersion::IPv4),
            'addresses.ipv6' => $addresses(AddressVersion::IPv6),
            'api_tokens.account' => PersonalAccessToken::query()->where('type', ApiKeyType::ACCOUNT->value)->count(),
            'api_tokens.application' => PersonalAccessToken::query()->where('type', ApiKeyType::APPLICATION->value)->count(),
            'backups' => Backup::query()->count(),
            'clusters' => Cluster::query()->count(),
            'image_definitions' => ImageDefinition::query()->count(),
            'image_versions' => ImageVersion::query()->count(),
            'invites.pending' => UserInvite::query()->where('expires_at', '>', $until)->count(),
            'isos' => ISO::query()->count(),
            'locations' => Location::query()->count(),
            'passkeys' => Passkey::query()->count(),
            'relays' => Relay::query()->whereNotNull('enrolled_at')->count(),
            'server_presets' => ServerPreset::query()->count(),
            'ssh_keys' => SSHKey::query()->count(),
            'storages' => Storage::query()->count(),
            'users.oauth' => User::query()->whereHas('oauthConnections')->count(),
            'users.passkeys' => User::query()->whereHas('passkeys')->count(),
            'users.two_factor' => User::query()->whereNotNull('two_factor_confirmed_at')->count(),
            'vlans' => Vlan::query()->count(),
        ];
    }

    /** @return array<string, bool> */
    private function features(): array
    {
        $enabledOAuth = array_keys($this->oauth->enabledProviders());
        $oauth = [];

        foreach (self::OAUTH_PROVIDERS as $provider) {
            $oauth["oauth.{$provider}"] = in_array($provider, $enabledOAuth, true);
        }

        $onS3 = fn (string $disk) => config('filesystems.disks.'.config($disk).'.driver') === 's3';

        return [
            'accounts.avatar_change' => $this->accounts->allow_avatar_change,
            'accounts.email_change' => $this->accounts->allow_email_change,
            'accounts.name_change' => $this->accounts->allow_name_change,
            'accounts.password_change' => $this->accounts->allow_password_change,
            'anchor.agents' => Node::query()->whereNotNull('agent_enrolled_at')->exists(),
            'anchor.panel_url' => $this->anchor->panel_url !== '',
            'anchor.relays' => Relay::query()->whereNotNull('enrolled_at')->exists(),
            'artifacts.s3' => $onS3('convoy.artifacts.disk'),
            'audit.pruning' => filled(config('audit.prune_days')),
            'audit.reveal_staff_identity' => $this->audit->reveal_staff_identity,
            'avatars.s3' => $onS3('convoy.avatars.disk'),
            'bandwidth.overage_disconnect' => $this->bandwidth->overage_action === 'disconnect',
            'mail' => $this->mail->isConfigured(),
            'metrics' => filled(config('metrics.victoriametrics.url')),
            ...$oauth,
            'oauth.registration' => (bool) config('oauth.registration'),
        ];
    }

    /**
     * Audit events recorded in the window, by event. Only values the catalog
     * still has are counted: a row left by an older release could carry
     * anything.
     *
     * @return array<string, int>
     */
    private function activity(CarbonImmutable $since, CarbonImmutable $until): array
    {
        $counts = AuditLog::query()
            ->toBase()
            ->where('created_at', '>', $since)
            ->where('created_at', '<=', $until)
            ->groupBy('event')
            ->selectRaw('event, count(*) as aggregate')
            ->pluck('aggregate', 'event');

        $activity = [];

        foreach ($counts as $event => $count) {
            if (AuditEvent::tryFrom((string) $event) !== null && TelemetryKey::valid((string) $event)) {
                $activity[(string) $event] = (int) $count;
            }
        }

        ksort($activity);

        return $activity;
    }

    private static function version(?string $value): ?string
    {
        return $value !== null && preg_match(self::VERSION, $value, $match) === 1 ? $match[1] : null;
    }
}
