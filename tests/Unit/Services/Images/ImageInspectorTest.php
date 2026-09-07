<?php

use App\Services\Images\ImageInspector;

/** A qcow2 header: magic, version 3, then the fields up to the virtual size. */
function qcowHeader(int $virtualSize): string
{
    return "QFI\xfb"
        .pack('N', 3)          // version
        .pack('J', 0)          // backing file offset
        .pack('N', 0)          // backing file size
        .pack('N', 16)         // cluster bits
        .pack('J', $virtualSize);
}

it('reads the provisioned size out of a qcow2 header', function () {
    // The number that matters is the size the disk will occupy once imported,
    // not the size of the file: it is the floor a plan has to clear.
    expect((new ImageInspector)->virtualSizeFromHeader(qcowHeader(8 * 1024 ** 3)))
        ->toBe(8 * 1024 ** 3);
});

it('declines to guess for anything that is not a qcow2', function () {
    expect((new ImageInspector)->virtualSizeFromHeader('not an image at all'))->toBeNull();
});

it('declines a truncated header rather than reading past it', function () {
    expect((new ImageInspector)->virtualSizeFromHeader(substr(qcowHeader(1024), 0, 20)))->toBeNull();
});

it('treats a declared size of zero as unknown', function () {
    // Zero unpacks perfectly well and describes no usable disk, so accepting it
    // would set a floor of nothing and let any plan through.
    expect((new ImageInspector)->virtualSizeFromHeader(qcowHeader(0)))->toBeNull();
});

it('reads a real file from disk', function () {
    $path = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($path, qcowHeader(4 * 1024 ** 3).str_repeat("\0", 128));

    expect((new ImageInspector)->virtualSizeOfFile($path))->toBe(4 * 1024 ** 3);

    unlink($path);
});
