<?php

namespace App\Models;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ProgressMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property int $id
 * @property int $deployment_id
 * @property string $name
 * @property DeploymentStatus $status
 * @property ProgressMode $progress_mode
 * @property int $sequence
 * @property ?string $task_upid
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
        'progress_mode' => 'required|string|in:determinate,indeterminate',
        'sequence' => 'required|integer|min:0',
        'task_upid' => 'nullable|string|max:191',
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
            'progress_mode' => ProgressMode::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The only three ways a step's status may change. Each is a guarded
     * transition — an illegal or repeated call is a silent no-op, never a
     * corruption — so a job cannot flip a failed step to completed, a retry
     * cannot reset the clock, and a late write cannot un-finish a step.
     *
     * `run()` composes these into the common "do work, then done" shape so a
     * one-shot job can never forget to close its step. Steps whose work spans
     * several queued jobs (kick then poll) call the transitions directly.
     */
    public function markRunning(): void
    {
        // Idempotent: a retried job re-enters here, but we keep the original
        // started_at rather than resetting it on every attempt.
        if ($this->status !== DeploymentStatus::PENDING) {
            return;
        }

        $this->update([
            'status' => DeploymentStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markCompleted(): void
    {
        if ($this->status->isTerminal()) {
            return;
        }

        $this->update([
            'status' => DeploymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(?Throwable $exception = null): void
    {
        // A completed step stays completed; only a non-completed step can fail.
        if ($this->status === DeploymentStatus::COMPLETED) {
            return;
        }

        $this->update([
            'status' => DeploymentStatus::FAILED,
            'completed_at' => now(),
            // error_message is capped at 191 chars in the schema; a raw
            // provider message can be far longer and would blow up the write.
            'error_message' => Str::limit($exception?->getMessage() ?? 'Unknown error', 188),
        ]);
    }

    /**
     * Start this step's asynchronous remote task exactly once, then remember its
     * UPID. A single job both starts the task and polls it to completion by
     * releasing itself back onto the queue; on every run after the first —
     * retried or released — task_upid is already set, so the callback is
     * skipped and the command is never issued twice. This is the durable guard
     * that lets one job own a step whose work spans many invocations.
     */
    public function kickOnce(callable $kick): void
    {
        if ($this->task_upid !== null) {
            return;
        }

        $this->markRunning();

        $this->update(['task_upid' => $kick()]);
    }

    /**
     * Run one-shot work as this step: mark it running, do the work, and mark it
     * completed — but only if the work returns without throwing. A throw skips
     * completion and propagates, so Laravel retries the job and the step stays
     * RUNNING; the terminal FAILED write happens once, in the job's `failed()`
     * hook, after retries are exhausted. Completion is therefore tied to
     * success and can never be left dangling.
     */
    public function run(callable $work): void
    {
        $this->markRunning();

        $work($this);

        $this->markCompleted();
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
