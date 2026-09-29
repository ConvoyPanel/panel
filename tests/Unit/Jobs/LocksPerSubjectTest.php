<?php

use Convoy\Enums\Server\PowerAction;
use Convoy\Jobs\Node\MonitorIsoDownloadJob;
use Convoy\Jobs\Server\BuildServerJob;
use Convoy\Jobs\Server\DeleteServerJob;
use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Jobs\Server\MonitorBackupRestorationJob;
use Convoy\Jobs\Server\PurgeBackupsJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Jobs\Server\SyncBuildJob;
use Convoy\Jobs\Server\SyncNetworkSettings;
use Convoy\Jobs\Server\UpdatePasswordJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;

// A lock that never expires outlives any job the worker kills for running past
// its timeout, and blocks that job class for that server until cleared by hand.
it('gives each per-subject lock an expiry past the job timeout', function (object $job) {
    $lock = collect($job->middleware())->first(fn ($m) => $m instanceof WithoutOverlapping);

    expect($lock)->not->toBeNull()
        ->and($lock->key)->toBe(42)
        ->and($lock->expiresAfter)->toBeGreaterThan($job->timeout)
        ->and($lock->releaseAfter)->toBeGreaterThan(0);
})->with([
    'power' => fn () => new SendPowerCommandJob(42, PowerAction::START),
    'password' => fn () => new UpdatePasswordJob(42, 'Rt-Aa1!bcdefgh9'),
    'build' => fn () => new BuildServerJob(42, 1),
    'delete' => fn () => new DeleteServerJob(42),
    'purge backups' => fn () => new PurgeBackupsJob(42),
    'sync build' => fn () => new SyncBuildJob(42),
    'sync network' => fn () => new SyncNetworkSettings(42),
    'monitor backup' => fn () => new MonitorBackupJob(42, 'UPID:x'),
    'monitor restore' => fn () => new MonitorBackupRestorationJob(42, 'UPID:x'),
    'monitor ISO download' => fn () => new MonitorIsoDownloadJob(42, 'UPID:x'),
]);
