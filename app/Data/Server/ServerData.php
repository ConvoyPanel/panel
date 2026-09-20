<?php

namespace App\Data\Server;

use App\Data\Node\NodeData;
use App\Enums\Server\PowerState;
use App\Enums\Server\ServerLifecycle;
use App\Enums\Server\ServerPermission;
use App\Models\Server;
use App\Models\User;
use App\Services\Nodes\GuestStateCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ServerData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $uuidShort,
        public int $userId,
        public int $nodeId,
        public ?int $networkInterfaceId,
        public int $vmid,
        public string $hostname,
        public string $name,
        public ?string $description,
        /**
         * Where Convoy has this server in its provisioning lifecycle.
         *
         * One of three independent facts about a server's condition, none of which is
         * derived from the others -- see `$suspendedAt` and `$powerState`.
         */
        public ServerLifecycle $lifecycle,
        /**
         * When the server was administratively suspended, or null if it isn't.
         *
         * Kept apart from `$lifecycle` on purpose: suspension coexists with any stage, so a
         * suspended server still reports the lifecycle it was in. Consumers deciding whether
         * a server is usable have to check both.
         */
        public ?CarbonImmutable $suspendedAt,
        /**
         * When the placement reconciler flagged this server for a human, and
         * why -- see ServerPlacementService. Admin-only: the reason names
         * nodes and bridges, and both fields read as null to clients (same
         * treatment as BackupEloquentData::$errorMessage).
         */
        public ?CarbonImmutable $flaggedAt,
        public ?string $flagReason,
        /**
         * Power state as of the last poll, or null for "we cannot say".
         *
         * The hypervisor's fact, not Convoy's: it says whether the guest is running right
         * now and nothing about whether the server is built or suspended. Read from the
         * poller's cache -- never from PVE, so a list of servers on a dead node costs
         * nothing to draw.
         */
        public ?PowerState $powerState,
        public int $cpu,
        public int $memory,
        public int $disk,
        public int $bandwidthUsage,
        public int $backupCountLimit,
        public int $backupSizeLimit,
        /**
         * Whether the node has a storage capable of holding backups. Without one
         * BackupCreationService refuses the request, so the client needs to know
         * before it offers the action rather than after the form is submitted.
         */
        public bool $hasBackupStorage,
        public int $bandwidthLimit,
        public ?int $speedLimit,
        public ?OveragePenaltyData $overagePenalty,
        public ?int $vlanTag,
        public CarbonImmutable $createdAt,
        /**
         * Whether the account this payload was built for owns the server, and what it may do on
         * it. A sub-user gets the server's own permission list; the owner gets all of them.
         *
         * Here rather than on a second request because every server screen needs it to decide
         * which tabs and buttons exist, and a page that renders a control the API will refuse is
         * worse than one that never offered it.
         */
        public bool $isOwner,
        /** @var list<ServerPermission> */
        public array $permissions,
        #[LoadRelation]
        public Lazy|NodeData $node,
    ) {}

    public static function fromModel(Server $server): self
    {
        $viewer = self::viewer();

        return new self(
            id: $server->id,
            uuid: $server->uuid,
            uuidShort: $server->uuid_short,
            userId: $server->user_id,
            nodeId: $server->node_id,
            networkInterfaceId: $server->network_interface_id,
            vmid: $server->vmid,
            hostname: $server->hostname,
            name: $server->name,
            description: $server->description,
            lifecycle: $server->lifecycle,
            suspendedAt: $server->suspended_at,
            flaggedAt: self::viewerIsStaff() ? $server->flagged_at : null,
            flagReason: self::viewerIsStaff() ? $server->flag_reason : null,
            powerState: app(GuestStateCache::class)->stateFor($server),
            cpu: $server->cpu,
            memory: (int) $server->memory,
            disk: (int) $server->disk,
            bandwidthUsage: (int) ($server->bandwidth_usage ?? 0),
            backupCountLimit: $server->backup_count_limit,
            backupSizeLimit: $server->backup_size_limit,
            hasBackupStorage: $server->node->hasBackupStorage(),
            bandwidthLimit: (int) $server->bandwidth_limit,
            speedLimit: $server->speed_limit,
            overagePenalty: $server->overage_penalty,
            vlanTag: $server->vlan_tag,
            createdAt: CarbonImmutable::parse($server->created_at),
            isOwner: $viewer !== null && $viewer->id === $server->user_id,
            permissions: self::permissionsFor($server, $viewer),
            node: Lazy::whenLoaded(
                'node',
                $server,
                fn () => NodeData::from($server->node),
            ),
        );
    }

    private static function viewer(): ?User
    {
        return Auth::user() instanceof User ? Auth::user() : null;
    }

    /** Fields the customer has no business seeing are gated on staff, not on ownership. */
    private static function viewerIsStaff(): bool
    {
        return self::viewer()?->isAdmin() === true;
    }

    /**
     * @return list<ServerPermission>
     */
    private static function permissionsFor(Server $server, ?User $viewer): array
    {
        if ($viewer === null) {
            return [];
        }

        // The owner and an operator both act without a stored grant, so they are handed the whole
        // catalog rather than a subset the frontend would then have to special-case.
        if ($viewer->id === $server->user_id || $viewer->isAdmin()) {
            return ServerPermission::cases();
        }

        return $server->subuserFor($viewer)?->grantedPermissions() ?? [];
    }
}
