<?php

use Convoy\Enums\Server\DiskInterface;
use Convoy\Exceptions\Service\Server\Allocation\DiskResizeFailedException;
use Convoy\Services\Servers\DiskResizeService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

const RESIZE_UPID = 'UPID:node:0000ABCD:00000000:00000000:resize:100:root@pam:';

/** Verbatim from a node that hit Proxmox bug #7598 during a build. */
const RESIZE_TIMEOUT = "command '/usr/bin/qemu-img resize '--preallocation=off' -f raw "
    ."/var/lib/vz/images/100/vm-100-disk-0.raw 53687091200' failed: got timeout";

const RESIZE_TARGET_BYTES = 50 * 1024 * 1024 * 1024;

beforeEach(fn () => Sleep::fake());

afterEach(fn () => Sleep::fake(false));

/** A task status as `/nodes/{node}/tasks/{upid}/status` reports it. */
function taskStatus(array $extra = []): array
{
    return ['data' => array_merge([
        'upid' => RESIZE_UPID,
        'node' => 'node',
        'pid' => 1234,
        'pstart' => 1234,
        'starttime' => 1700000000,
        'endtime' => 1700000001,
        'type' => 'resize',
        'id' => '100',
        'user' => 'root@pam',
        'status' => 'stopped',
        'exitstatus' => 'OK',
    ], $extra)];
}

function resizeRunning(): array
{
    return ['status' => 'running', 'exitstatus' => null, 'endtime' => null];
}

function resizeFailed(string $error): array
{
    return ['exitstatus' => $error];
}

/** Fake the resize call, then hand back $statuses in order as the task is polled. */
function fakeResizeRun(array $statuses): void
{
    $sequence = Http::sequence();

    foreach ($statuses as $status) {
        $sequence->push(taskStatus($status), 200);
    }

    Http::fake([
        '*/tasks/*/status' => $sequence,
        '*/resize' => Http::response(['data' => RESIZE_UPID], 200),
    ]);
}

function resizeCallsSent(): int
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/resize'))
        ->count();
}

it('waits for the resize task instead of returning as soon as PVE accepts it', function () {
    fakeResizeRun([resizeRunning(), []]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->handle($server, DiskInterface::SCSI0, RESIZE_TARGET_BYTES);

    // The size goes out absolute (a relative `+N` would stack across retries).
    Http::assertSent(fn ($request) => str_contains($request->url(), '/resize')
        && $request->method() === 'PUT'
        && $request['disk'] === 'scsi0'
        && $request['size'] === '52428800K');

    expect(resizeCallsSent())->toBe(1);
    Http::assertSentCount(3);
});

it('retries once when the node times out on qemu-img, and succeeds', function () {
    fakeResizeRun([resizeFailed(RESIZE_TIMEOUT), []]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->handle($server, DiskInterface::SCSI0, RESIZE_TARGET_BYTES);

    expect(resizeCallsSent())->toBe(2);
    Sleep::assertSleptTimes(1);
    Sleep::assertSlept(fn ($duration) => (int) $duration->totalSeconds === 10);
});

it('gives up and reports the node error when the retry times out too', function () {
    fakeResizeRun([resizeFailed(RESIZE_TIMEOUT), resizeFailed(RESIZE_TIMEOUT)]);
    [, , , $server] = createServerModel();

    expect(fn () => app(DiskResizeService::class)->handle($server, DiskInterface::SCSI0, RESIZE_TARGET_BYTES))
        ->toThrow(DiskResizeFailedException::class, RESIZE_TIMEOUT);

    expect(resizeCallsSent())->toBe(2);
});

it('does not retry a failure that is not the timeout race', function () {
    fakeResizeRun([resizeFailed('unable to allocate space on storage')]);
    [, , , $server] = createServerModel();

    expect(fn () => app(DiskResizeService::class)->handle($server, DiskInterface::SCSI0, RESIZE_TARGET_BYTES))
        ->toThrow(DiskResizeFailedException::class);

    expect(resizeCallsSent())->toBe(1);
    Sleep::assertNeverSlept();
});

it('leaves a task that is still running when the window closes, rather than stacking a second one', function () {
    Http::fake([
        '*/tasks/*/status' => Http::response(taskStatus(resizeRunning()), 200),
        '*/resize' => Http::response(['data' => RESIZE_UPID], 200),
    ]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->handle($server, DiskInterface::SCSI0, RESIZE_TARGET_BYTES);

    expect(resizeCallsSent())->toBe(1);
    Http::assertSentCount(61);
});
