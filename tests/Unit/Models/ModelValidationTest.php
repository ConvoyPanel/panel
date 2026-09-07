<?php

use App\Exceptions\Model\DataValidationException;
use App\Models\ImageGroup;

/**
 * The base model validates on `saving`. That listener deliberately returns
 * nothing: Eloquent halts an event chain on the first non-null return, and this
 * one is registered before every listener a subclass adds, so returning a value
 * here silently disabled them. These pin the behaviour that actually matters --
 * a bad save throws, a good one goes through -- so the return value stays a
 * detail nobody has to preserve by accident.
 */
it('refuses to save a model that breaks its own rules', function () {
    expect(fn () => ImageGroup::create(['name' => str_repeat('a', 41)]))
        ->toThrow(DataValidationException::class);
});

it('saves a model that satisfies them', function () {
    $group = ImageGroup::create(['name' => 'Ubuntu']);

    expect($group->exists)->toBeTrue()
        ->and($group->fresh()->name)->toBe('Ubuntu');
});

it('runs a subclass listener registered after the validator', function () {
    // The regression this guards: a `creating`/`updating` listener on a
    // subclass must still fire. ImageGroup mints its uuid in one.
    $group = ImageGroup::create(['name' => 'Debian']);

    expect($group->uuid)->not->toBeNull();
});
