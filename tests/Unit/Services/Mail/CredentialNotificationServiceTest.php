<?php

use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Notifications\ServerCredentialsNotification;
use Convoy\Notifications\UserCredentialsNotification;
use Convoy\Services\Mail\CredentialNotificationService;
use Illuminate\Support\Facades\Notification;

it('sends user credentials when user credential emails are enabled', function () {
    Notification::fake();
    config(['convoy.credentials_mail.users.enabled' => true]);

    $user = new User([
        'name' => 'Example User',
        'email' => 'user@example.com',
    ]);
    $user->id = 42;

    app(CredentialNotificationService::class)->sendUserCredentials($user, 'Password123!');

    Notification::assertSentTo($user, UserCredentialsNotification::class);
});

it('does not send user credentials when user credential emails are disabled', function () {
    Notification::fake();
    config(['convoy.credentials_mail.users.enabled' => false]);

    $user = new User([
        'name' => 'Example User',
        'email' => 'user@example.com',
    ]);
    $user->id = 42;

    app(CredentialNotificationService::class)->sendUserCredentials($user, 'Password123!');

    Notification::assertNothingSent();
});

it('sends server credentials when server credential emails are enabled', function () {
    Notification::fake();
    config(['convoy.credentials_mail.servers.enabled' => true]);

    $user = new User([
        'name' => 'Example User',
        'email' => 'user@example.com',
    ]);
    $user->id = 42;

    $server = new Server([
        'name' => 'Demo Server',
        'hostname' => 'demo.example.com',
        'uuid_short' => 'abc12345',
    ]);
    $server->id = 100;
    $server->setRelation('user', $user);

    app(CredentialNotificationService::class)->sendServerCredentials($server, 'root', 'Password123!');

    Notification::assertSentTo($user, ServerCredentialsNotification::class);
});

it('does not send server credentials when server credential emails are disabled', function () {
    Notification::fake();
    config(['convoy.credentials_mail.servers.enabled' => false]);

    $user = new User([
        'name' => 'Example User',
        'email' => 'user@example.com',
    ]);
    $user->id = 42;

    $server = new Server([
        'name' => 'Demo Server',
        'hostname' => 'demo.example.com',
        'uuid_short' => 'abc12345',
    ]);
    $server->id = 100;
    $server->setRelation('user', $user);

    app(CredentialNotificationService::class)->sendServerCredentials($server, 'root', 'Password123!');

    Notification::assertNothingSent();
});
