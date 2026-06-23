<?php

use Convoy\Jobs\User\SendUserCredentialsEmailJob;
use Convoy\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues a user credential email when the feature is enabled', function () {
    config(['convoy.credentials_mail.users.enabled' => true]);

    $admin = User::factory()->create([
        'root_admin' => true,
    ]);

    $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Example User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'root_admin' => false,
    ])->assertOk();

    Queue::assertPushed(SendUserCredentialsEmailJob::class);
});

it('does not queue a user credential email when the feature is disabled', function () {
    config(['convoy.credentials_mail.users.enabled' => false]);

    $admin = User::factory()->create([
        'root_admin' => true,
    ]);

    $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Example User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'root_admin' => false,
    ])->assertOk();

    Queue::assertNotPushed(SendUserCredentialsEmailJob::class);
});
