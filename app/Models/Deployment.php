<?php

namespace App\Models;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $server_id
 * @property ?int $image_definition_id
 * @property ?int $image_version_id
 * @property DeploymentType $type
 * @property DeploymentStatus $status
 * @property bool $start_on_completion
 * @property Server $server
 * @property ?ImageDefinition $imageDefinition
 * @property ?ImageVersion $imageVersion
 */
class Deployment extends Model
{
    const UPDATED_AT = null;

    const CREATED_AT = null;

    /**
     * Fields that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * Rules ensuring that the raw data stored in the database meets expectations.
     */
    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'image_definition_id' => 'nullable|exists:image_definitions,id',
        'image_version_id' => 'nullable|exists:image_versions,id',
        'type' => 'required|string|in:install,reinstall,delete,import',
        'status' => 'required|string|in:pending,running,completed,failed',
        'start_on_completion' => 'required|boolean',
        'requested_at' => 'required|date',
        'started_at' => 'nullable|date',
        'completed_at' => 'nullable|date',
    ];

    public function casts(): array
    {
        return [
            'type' => DeploymentType::class,
            'status' => DeploymentStatus::class,
            'should_create_vm' => 'boolean',
            'start_on_completion' => 'boolean',
            'requested_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * What was chosen. Kept beside the version so a deployment still says which
     * image an operator picked even after that image is rebuilt or retired.
     *
     * @return BelongsTo<ImageDefinition, $this>
     */
    public function imageDefinition(): BelongsTo
    {
        return $this->belongsTo(ImageDefinition::class);
    }

    /**
     * What was actually built. The disks and hashes this server came from.
     *
     * @return BelongsTo<ImageVersion, $this>
     */
    public function imageVersion(): BelongsTo
    {
        return $this->belongsTo(ImageVersion::class);
    }

    /**
     * @return HasMany<DeploymentStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(DeploymentStep::class)
            ->orderBy('sequence')
            ->orderBy('id');
    }

    /**
     * Create steps, stamping each with the next sequence number so their display
     * order is explicit and stays correct even when steps are added by more than
     * one action (a reinstall appends build steps after delete steps).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, DeploymentStep>
     */
    public function addSteps(array $rows): Collection
    {
        $rows = array_values($rows);
        $next = (int) $this->steps()->max('sequence');

        foreach ($rows as $i => $row) {
            $rows[$i]['sequence'] = $next + $i + 1;
        }

        return $this->steps()->createMany($rows);
    }

    public function scopeNonCompleted(Builder $query): void
    {
        $query->whereIn('status', [DeploymentStatus::PENDING, DeploymentStatus::RUNNING]);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
