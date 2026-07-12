<?php

use App\Extensions\Spatie\Data\Proxmox\Casts\RateLimitCast;

it('parses Proxmox decimal MB/s into bytes/s', function () {
    $cast = new RateLimitCast;

    expect($cast->parse('100'))->toBe(100_000_000)
        ->and($cast->parse('1'))->toBe(1_000_000)
        ->and($cast->parse('1.5'))->toBe(1_500_000);
});

it('emits bytes/s back as decimal MB/s', function () {
    $cast = new RateLimitCast;

    expect($cast->emit(100_000_000))->toBe('100')
        ->and($cast->emit(1_000_000))->toBe('1')
        ->and($cast->emit(1_500_000))->toBe('1.5');
});

it('does not use binary MiB (the pre-fix bug)', function () {
    // 100 MB/s must be 100_000_000 bytes/s, not 100 * 1024 * 1024.
    expect((new RateLimitCast)->parse('100'))->not->toBe(104_857_600);
});
