<?php

namespace App\Data\Server;

use App\Data\Node\NodeData;
use App\Enums\Server\ServerStatus;
use App\Models\Server;
use Carbon\CarbonImmutable;
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
        public ServerStatus $status,
        public int $cpu,
        public int $memory,
        public int $disk,
        public int $bandwidthUsage,
        public int $backupCountLimit,
        public int $backupSizeLimit,
        public int $bandwidthLimit,
        public ?int $speedLimit,
        public ?OveragePenaltyData $overagePenalty,
        public ?int $vlanTag,
        public CarbonImmutable $createdAt,
        #[LoadRelation]
        public Lazy|NodeData $node,
    ) {}

    public static function fromModel(Server $server): self
    {
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
            status: $server->status,
            cpu: $server->cpu,
            memory: (int) $server->memory,
            disk: (int) $server->disk,
            bandwidthUsage: (int) ($server->bandwidth_usage ?? 0),
            backupCountLimit: $server->backup_count_limit,
            backupSizeLimit: $server->backup_size_limit,
            bandwidthLimit: (int) $server->bandwidth_limit,
            speedLimit: $server->speed_limit,
            overagePenalty: $server->overage_penalty,
            vlanTag: $server->vlan_tag,
            createdAt: CarbonImmutable::parse($server->created_at),
            node: Lazy::whenLoaded(
                'node',
                $server,
                fn () => NodeData::from($server->node),
            ),
        );
    }
}
