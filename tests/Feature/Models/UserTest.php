<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('hashes raw passwords when they are assigned', function () {
    $user = User::factory()->create([
        'password' => 'Password1!',
    ]);

    expect($user->password)->not->toBe('Password1!')
        ->and(Hash::check('Password1!', $user->password))->toBeTrue();
});

it('does not rehash already hashed passwords', function () {
    $hash = Hash::make('Password1!');

    $user = User::factory()->create([
        'password' => $hash,
    ]);

    expect($user->password)->toBe($hash);
});
