<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A storage scope: a PVE cluster, or a standalone node's private scope.
 *
 * Identified by the cluster CA's fingerprint, never by name -- see the
 * `create_clusters_table` migration for why. A null fingerprint means this is
 * the singleton scope of one standalone node; such a scope is keyed by being
 * referenced from that node's `cluster_id`, deliberately not by certificate
 * (a node separated from a cluster without a reinstall still carries the old
 * cluster's CA).
 *
 * @property int $id
 * @property ?string $fingerprint
 * @property ?string $name
 * @property ?array<int, string> $member_names
 * @property ?CarbonImmutable $flagged_at
 * @property ?string $flag_reason
 */
class Cluster extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'member_names' => 'array',
            'flagged_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * @return HasMany<Storage, $this>
     */
    public function storages(): HasMany
    {
        return $this->hasMany(Storage::class);
    }

    public function isStandalone(): bool
    {
        return $this->fingerprint === null;
    }
}
