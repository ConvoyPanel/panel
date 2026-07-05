<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a non-admin user when the admin option is false', function () {
    $this->artisan('c:user:make', [
        '--email' => 'cli@example.com',
        '--name' => 'CLI User',
        '--password' => 'Password1!',
        '--admin' => 'false',
    ])->assertSuccessful();

    $user = User::where('email', 'cli@example.com')->firstOrFail();

    expect($user->root_admin)->toBeFalse()
        ->and(Hash::check('Password1!', $user->password))->toBeTrue();
});

it('rejects invalid admin option values', function () {
    $this->artisan('c:user:make', [
        '--email' => 'invalid-admin@example.com',
        '--name' => 'Invalid Admin',
        '--password' => 'Password1!',
        '--admin' => 'not-a-boolean',
    ])->assertFailed();

    expect(User::where('email', 'invalid-admin@example.com')->exists())->toBeFalse();
});
