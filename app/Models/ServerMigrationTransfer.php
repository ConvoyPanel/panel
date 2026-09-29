<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One guest's journey across the Anchor transport.
 *
 * Written before anything remote is asked for and filled in as each step
 * learns something the next one needs. The row exists so the rollback does not
 * have to reconstruct the attempt from a chain it was not part of: whatever is
 * set here is what has to be undone, and whatever is null never happened.
 *
 * @property int $id
 * @property int $deployment_id
 * @property int $server_id
 * @property int $source_node_id
 * @property int $destination_node_id
 * @property int $source_vmid
 * @property int $destination_vmid
 * @property string $destination_storage
 * @property ?string $export_job_id
 * @property ?string $install_job_id
 * @property ?string $artifact
 * @property ?string $sha256
 * @property ?int $size
 * @property int $export_attempts
 * @property bool $was_running
 * @property ?CarbonImmutable $verified_at
 * @property ?CarbonImmutable $source_destroyed_at
 * @property ?CarbonImmutable $rolled_back_at
 * @property Deployment $deployment
 * @property Server $server
 * @property Node $sourceNode
 * @property Node $destinationNode
 */
class ServerMigrationTransfer extends Model
{
    protected $guarded = ['id'];

    public static array $validationRules = [
        'deployment_id' => 'required|integer|exists:deployments,id',
        'server_id' => 'required|integer|exists:servers,id',
        'source_node_id' => 'required|integer|exists:nodes,id',
        'destination_node_id' => 'required|integer|exists:nodes,id',
        'source_vmid' => 'required|integer|min:100',
        'destination_vmid' => 'required|integer|min:100',
        'destination_storage' => 'required|string|max:191',
        'export_job_id' => 'nullable|string|max:191',
        'install_job_id' => 'nullable|string|max:191',
        'artifact' => 'nullable|string|max:191',
        'sha256' => 'nullable|string|size:64',
        'size' => 'nullable|integer|min:0',
        'export_attempts' => 'sometimes|integer|min:0',
        'was_running' => 'sometimes|boolean',
        'verified_at' => 'nullable|date',
        'source_destroyed_at' => 'nullable|date',
        'rolled_back_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'source_vmid' => 'integer',
            'destination_vmid' => 'integer',
            'size' => 'integer',
            'export_attempts' => 'integer',
            'was_running' => 'boolean',
            'verified_at' => 'datetime',
            'source_destroyed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    /** How many dumps this transfer is allowed before it gives up. */
    public const MAX_EXPORT_ATTEMPTS = 2;

    /**
     * Whether the destination guest has been checked and found good. The
     * source guest may not be destroyed without this.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * The point of no return. Past it there is no source guest to restart and
     * no earlier state to go back to, so a rollback would be a second failure
     * rather than a recovery.
     */
    public function isCommitted(): bool
    {
        return $this->source_destroyed_at !== null;
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'source_node_id');
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function destinationNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'destination_node_id');
    }
}
