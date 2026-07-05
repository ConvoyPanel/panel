<?php

namespace App\Models;

use App\Enums\Server\DeploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $deployment_id
 * @property string $name
 * @property DeploymentStatus $status
 * @property ?int $progress_current
 * @property ?int $progress_total
 * @property ?string $error_code
 * @property ?string $error_message
 * @property Deployment $deployment
 */
class DeploymentStep extends Model
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
        'deployment_id' => 'required|exists:deployments,id',
        'name' => 'required|string|max:191',
        'status' => 'required|string|in:running,completed,failed',
        'progress_total' => 'nullable|integer|min:0',
        'progress_current' => 'nullable|integer|min:0',
        'started_at' => 'nullable|date',
        'completed_at' => 'nullable|date',
        'error_code' => 'nullable|string|max:191',
        'error_message' => 'nullable|string|max:191',
    ];

    public function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function start(): void
    {
        $this->update([
            'status' => DeploymentStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => DeploymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
