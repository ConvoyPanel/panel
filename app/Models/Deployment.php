<?php

namespace App\Models;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $server_id
 * @property ?int $template_id
 * @property DeploymentType $type
 * @property DeploymentStatus $status
 * @property bool $start_on_completion
 * @property Server $server
 * @property ?Template $template
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
        'template_id' => 'nullable|exists:templates,id',
        'type' => 'required|string|in:install,reinstall,delete,import',
        'status' => 'required|string|in:pending,running,completed,failed',
        'start_on_completion' => 'required|boolean',
        'requested_at' => 'required|date',
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
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return HasMany<DeploymentStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(DeploymentStep::class);
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
