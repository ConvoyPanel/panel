<?php

namespace App\Models;

use App\Enums\Provisioner\ProvisioningStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * One server's provisioner scripts, from the snippet being written until it is
 * deleted again.
 *
 * Field values are not stored here. They go from the request into the queued
 * job and from there into the snippet, and the snippet is deleted once the
 * guest reports back.
 *
 * @property int $id
 * @property string $uuid
 * @property ?int $server_id
 * @property ?int $deployment_id
 * @property int $node_id
 * @property string $storage
 * @property string $snippet
 * @property string $token_hash
 * @property ProvisioningStatus $status
 * @property array<int, array{uuid: string, name: string}> $provisioners
 * @property ?array<int, array{provisioner: string, exit_code: int}> $results
 * @property ?CarbonImmutable $deadline_at
 * @property ?CarbonImmutable $reported_at
 * @property ?CarbonImmutable $cleaned_up_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property ?Server $server
 * @property ?Deployment $deployment
 * @property Node $node
 */
class ProvisioningRun extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ProvisioningStatus::class,
            'provisioners' => 'array',
            'results' => 'array',
            'deadline_at' => 'immutable_datetime',
            'reported_at' => 'immutable_datetime',
            'cleaned_up_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /** The volume id `cicustom` points at, e.g. `local:snippets/convoy-provisioner-….yaml`. */
    public function volume(): string
    {
        return "{$this->storage}:snippets/{$this->snippet}";
    }

    public static function snippetNameFor(string $uuid): string
    {
        return "convoy-provisioner-{$uuid}.yaml";
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Start the timeout on a server's run once the server is started.
     *
     * A server built without starting may sit stopped for days, and its
     * scripts have not had a chance to run. The clock starts the first time
     * the panel starts it.
     */
    public static function startClockFor(Server $server): void
    {
        $minutes = max(1, (int) config('convoy.provisioners.timeout_minutes', 60));

        self::query()
            ->where('server_id', $server->id)
            ->where('status', ProvisioningStatus::PENDING)
            ->whereNull('deadline_at')
            ->update(['deadline_at' => now()->addMinutes($minutes)]);
    }

    public function scopeUncleaned(Builder $query): void
    {
        $query->whereNull('cleaned_up_at');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->uuid ??= Uuid::uuid4()->toString();
            $model->snippet ??= self::snippetNameFor($model->uuid);
        });
    }
}
