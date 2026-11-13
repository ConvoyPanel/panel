<?php

use Convoy\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an administrator with --admin=true', function () {
    $this->artisan('users:create', [
        '--email' => 'admin@example.com',
        '--name' => 'Administrator',
        '--password' => 'Correct-horse-1!',
        '--admin' => 'true',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@example.com')->firstOrFail();

    expect($user->root_admin)->toBeTrue()
        ->and(Hash::check('Correct-horse-1!', $user->password))->toBeTrue();
});

// (bool) "false" is true, so this used to create an administrator.
it('creates a regular user with --admin=false', function (string $admin) {
    $this->artisan('c:user:make', [
        '--email' => 'user@example.com',
        '--name' => 'User',
        '--password' => 'Correct-horse-1!',
        '--admin' => $admin,
    ])->assertSuccessful();

    expect(User::where('email', 'user@example.com')->firstOrFail()->root_admin)->toBeFalse();
})->with(['false', '0', 'no']);

it('refuses an --admin value that is not a boolean', function (string $admin) {
    $this->artisan('users:create', [
        '--email' => 'user@example.com',
        '--name' => 'User',
        '--password' => 'Correct-horse-1!',
        '--admin' => $admin,
    ])->assertFailed();

    expect(User::where('email', 'user@example.com')->exists())->toBeFalse();
})->with(['', 'maybe']);

it('refuses a password the panel would refuse', function () {
    $this->artisan('users:create', [
        '--email' => 'user@example.com',
        '--name' => 'User',
        '--password' => 'password',
        '--admin' => 'false',
    ])->assertFailed();

    expect(User::where('email', 'user@example.com')->exists())->toBeFalse();
});
