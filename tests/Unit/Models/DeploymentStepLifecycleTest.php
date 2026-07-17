<?php

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Models\Deployment;
use App\Models\DeploymentStep;

/**
 * Create a persisted step in a given status. The lifecycle guards read the
 * status from the model, so these tests exercise real DB writes.
 */
function makeStep(DeploymentStatus $status = DeploymentStatus::PENDING): DeploymentStep
{
    [, , , $server] = createServerModel();

    $deployment = Deployment::create([
        'server_id' => $server->id,
        'type' => DeploymentType::INSTALL,
        'status' => DeploymentStatus::PENDING,
        'start_on_completion' => false,
        'requested_at' => now(),
    ]);

    return $deployment->steps()->create([
        'name' => 'clone',
        'status' => $status,
        'progress_total' => 10,
    ]);
}

it('opens a pending step and stamps started_at', function () {
    $step = makeStep();

    $step->markRunning();

    expect($step->status)->toBe(DeploymentStatus::RUNNING)
        ->and($step->started_at)->not->toBeNull();
});

it('does not reset started_at when a running step is re-opened on retry', function () {
    $step = makeStep();
    $step->markRunning();
    $firstStartedAt = $step->started_at;

    $this->travel(5)->minutes();
    $step->markRunning();

    expect($step->started_at->timestamp)->toBe($firstStartedAt->timestamp);
});

it('refuses to complete a step that already failed', function () {
    $step = makeStep(DeploymentStatus::FAILED);

    $step->markCompleted();

    expect($step->fresh()->status)->toBe(DeploymentStatus::FAILED);
});

it('refuses to fail a step that already completed', function () {
    $step = makeStep(DeploymentStatus::COMPLETED);

    $step->markFailed(new RuntimeException('too late'));

    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});

it('truncates an over-long provider error to fit the column', function () {
    $step = makeStep(DeploymentStatus::RUNNING);

    $step->markFailed(new RuntimeException(str_repeat('x', 500)));

    expect($step->status)->toBe(DeploymentStatus::FAILED)
        ->and(mb_strlen($step->error_message))->toBeLessThanOrEqual(191);
});

it('completes a step only when the work returns', function () {
    $step = makeStep();

    $step->run(fn () => null);

    expect($step->status)->toBe(DeploymentStatus::COMPLETED)
        ->and($step->completed_at)->not->toBeNull();
});

it('leaves a step running (not completed) when the work throws', function () {
    $step = makeStep();

    expect(fn () => $step->run(function () {
        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class);

    // The throw propagates for Laravel to retry; the terminal FAILED write is
    // the job's failed() hook, not run(). The step must not be COMPLETED here.
    expect($step->fresh()->status)->toBe(DeploymentStatus::RUNNING);
});
