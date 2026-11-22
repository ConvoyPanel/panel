<?php

use App\Actions\Server\BuildServerAction;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\ServerLifecycle;
use App\Models\Deployment;
use App\Notifications\ServerReady;
use App\Settings\AccountSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $mail = app(MailSettings::class);
    $mail->host = 'smtp.example.com';
    $mail->save();

    [$this->owner, , , $this->server] = createServerModel();
});

/**
 * Run the closure a build chain ends with, the way the queue would once every job before it has
 * finished. The jobs themselves need a node, and none of them decide whether this email goes out.
 */
function completeDeployment(Deployment $deployment): void
{
    $action = app(BuildServerAction::class);

    (new ReflectionMethod($action, 'onComplete'))->invoke($action, $deployment)();
}

function deploymentOf(DeploymentType $type): Deployment
{
    return Deployment::create([
        'server_id' => test()->server->id,
        'type' => $type,
        'status' => DeploymentStatus::RUNNING,
        'start_on_completion' => false,
        'requested_at' => now(),
    ]);
}

it('emails the owner when a new server finishes installing', function () {
    $this->server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

    completeDeployment(deploymentOf(DeploymentType::INSTALL));

    expect($this->server->fresh()->lifecycle)->toBe(ServerLifecycle::READY);

    Notification::assertSentTo($this->owner, ServerReady::class);
});

it('emails the owner when an existing guest is imported', function () {
    completeDeployment(deploymentOf(DeploymentType::IMPORT));

    Notification::assertSentTo($this->owner, ServerReady::class);
});

it('stays quiet for a reinstall or a migration', function (DeploymentType $type) {
    // Both are things that happen to a server the owner already has.
    completeDeployment(deploymentOf($type));

    Notification::assertNothingSent();
})->with([
    'reinstall' => DeploymentType::REINSTALL,
    'migration' => DeploymentType::MIGRATE,
]);

it('stays quiet when an admin has turned it off', function () {
    $settings = app(AccountSettings::class);
    $settings->send_server_ready_email = false;
    $settings->save();

    completeDeployment(deploymentOf(DeploymentType::INSTALL));

    Notification::assertNothingSent();
});

it('stays quiet when the panel has no mail relay', function () {
    $mail = app(MailSettings::class);
    $mail->host = '';
    $mail->save();
    config()->set('mail.default', 'array');

    completeDeployment(deploymentOf(DeploymentType::INSTALL));

    Notification::assertNothingSent();
});

it('sends nothing to subusers', function () {
    $subuser = subuser($this->server, []);

    completeDeployment(deploymentOf(DeploymentType::INSTALL));

    Notification::assertNotSentTo($subuser, ServerReady::class);
    Notification::assertSentTimes(ServerReady::class, 1);
});
