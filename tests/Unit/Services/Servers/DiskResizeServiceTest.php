<?php

use App\Data\Server\Proxmox\Config\DiskData;
use App\Exceptions\Service\Server\Allocation\DiskResizeFailedException;
use App\Services\Servers\DiskResizeService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

const RESIZE_UPID = 'UPID:node:0000ABCD:00000000:00000000:resize:100:root@pam:';

/** Verbatim from a node that hit Proxmox bug #7598 during a build. */
const RESIZE_TIMEOUT = "command '/usr/bin/qemu-img resize '--preallocation=off' -f raw "
    ."/var/lib/vz/images/100/vm-100-disk-0.raw 53687091200' failed: got timeout";

const RESIZE_TARGET_BYTES = 50 * 1024 * 1024 * 1024;

beforeEach(fn () => Sleep::fake());

afterEach(fn () => Sleep::fake(false));

function diskToGrow(): DiskData
{
    return DiskData::fromRaw('sata0', 'local-lvm:vm-100-disk-0,size=4712M');
}

/** A task status reporting the resize still in flight. */
function resizeRunning(): array
{
    return ['status' => 'running', 'exitstatus' => null, 'endtime' => null];
}

/** A task status reporting the resize finished with $error. */
function resizeFailed(string $error): array
{
    return ['exitstatus' => $error];
}

/**
 * Fake the resize call, then hand back $statuses in order as the task is polled.
 */
function fakeResizeRun(array $statuses): void
{
    $sequence = Http::sequence();

    foreach ($statuses as $status) {
        $sequence->push(taskStatusFixture($status), 200);
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

    app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES);

    // The size goes out absolute (a relative `+N` would stack across retries).
    Http::assertSent(fn ($request) => str_contains($request->url(), '/resize')
        && $request->method() === 'PUT'
        && $request['disk'] === 'sata0'
        && $request['size'] === '52428800K');

    // One resize, then polled until it stopped: the call did not return early.
    expect(resizeCallsSent())->toBe(1);
    Http::assertSentCount(3);
});

it('retries once when the node times out on qemu-img, and succeeds', function () {
    fakeResizeRun([resizeFailed(RESIZE_TIMEOUT), []]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES);

    expect(resizeCallsSent())->toBe(2);

    // Only the pause between attempts -- both task reads answered immediately,
    // so nothing was spent polling.
    Sleep::assertSleptTimes(1);
    Sleep::assertSlept(fn ($duration) => (int) $duration->totalSeconds === 10);
});

it('retries a config lock it could not take, which is the same race seen from the other side', function () {
    fakeResizeRun([
        resizeFailed("can't lock file '/var/lock/qemu-server/lock-100.conf' - got timeout"),
        [],
    ]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES);

    expect(resizeCallsSent())->toBe(2);
});

it('gives up and reports the node error when the retry times out too', function () {
    fakeResizeRun([resizeFailed(RESIZE_TIMEOUT), resizeFailed(RESIZE_TIMEOUT)]);
    [, , , $server] = createServerModel();

    expect(fn () => app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES))
        ->toThrow(DiskResizeFailedException::class, RESIZE_TIMEOUT);

    expect(resizeCallsSent())->toBe(2);
});

it('does not retry a failure that is not the timeout race', function () {
    fakeResizeRun([resizeFailed('unable to allocate space on storage')]);
    [, , , $server] = createServerModel();

    expect(fn () => app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES))
        ->toThrow(DiskResizeFailedException::class);

    // Reissuing it would fail the same way, so only one attempt was made.
    expect(resizeCallsSent())->toBe(1);
    Sleep::assertNeverSlept();
});

it('leaves a task that is still running when the window closes, rather than stacking a second one', function () {
    Http::fake([
        '*/tasks/*/status' => Http::response(taskStatusFixture(resizeRunning()), 200),
        '*/resize' => Http::response(['data' => RESIZE_UPID], 200),
    ]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES);

    // Polled out its whole window, then returned without a second resize -- a
    // slow-but-working resize must not have a rival queued behind its own lock.
    expect(resizeCallsSent())->toBe(1);
    Http::assertSentCount(61);
});

it('treats a node that resized inline, returning no task, as done', function () {
    Http::fake(['*/resize' => Http::response(['data' => null], 200)]);
    [, , , $server] = createServerModel();

    app(DiskResizeService::class)->resize($server, diskToGrow(), RESIZE_TARGET_BYTES);

    // Nothing to wait on: no task status was read, and nothing was retried.
    expect(resizeCallsSent())->toBe(1);
    Http::assertSentCount(1);
});
