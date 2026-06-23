<?php

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Jobs\Server\SendServerCredentialsEmailJob;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Servers\ServerBuildDispatchService;

function buildJobsFor(ServerDeploymentData $deployment): array
{
    $method = new ReflectionMethod(ServerBuildDispatchService::class, 'getChainedBuildJobs');
    $method->setAccessible(true);

    return $method->invoke(app(ServerBuildDispatchService::class), $deployment);
}

function protectedJobProperty(object $job, string $property): mixed
{
    return (fn () => $this->{$property})->call($job);
}

it('appends server credential email job when enabled and a password is available', function () {
    config(['convoy.credentials_mail.servers.enabled' => true]);

    $server = new Server(['name' => 'Demo Server']);
    $server->id = 100;

    $template = new Template(['name' => 'Ubuntu 22.04']);
    $template->id = 200;

    $jobs = buildJobsFor(new ServerDeploymentData(
        server: $server,
        template: $template,
        account_password: 'Password123!',
        should_create_server: true,
        start_on_completion: false,
    ));

    $job = $jobs[array_key_last($jobs)];

    expect($job)->toBeInstanceOf(SendServerCredentialsEmailJob::class)
        ->and(protectedJobProperty($job, 'serverId'))->toBe(100)
        ->and(protectedJobProperty($job, 'username'))->toBe('root')
        ->and(protectedJobProperty($job, 'password'))->toBe('Password123!');
});

it('uses the Administrator username for Windows templates', function () {
    config(['convoy.credentials_mail.servers.enabled' => true]);

    $server = new Server(['name' => 'Demo Server']);
    $server->id = 100;

    $template = new Template(['name' => 'Windows Server 2022']);
    $template->id = 200;

    $jobs = buildJobsFor(new ServerDeploymentData(
        server: $server,
        template: $template,
        account_password: 'Password123!',
        should_create_server: true,
        start_on_completion: false,
    ));

    $job = $jobs[array_key_last($jobs)];

    expect($job)->toBeInstanceOf(SendServerCredentialsEmailJob::class)
        ->and(protectedJobProperty($job, 'username'))->toBe('Administrator');
});

it('does not append server credential email job when disabled', function () {
    config(['convoy.credentials_mail.servers.enabled' => false]);

    $server = new Server(['name' => 'Demo Server']);
    $server->id = 100;

    $template = new Template(['name' => 'Ubuntu 22.04']);
    $template->id = 200;

    $jobs = buildJobsFor(new ServerDeploymentData(
        server: $server,
        template: $template,
        account_password: 'Password123!',
        should_create_server: true,
        start_on_completion: false,
    ));

    expect($jobs[array_key_last($jobs)])->not->toBeInstanceOf(SendServerCredentialsEmailJob::class);
});

it('does not append server credential email job without a password', function () {
    config(['convoy.credentials_mail.servers.enabled' => true]);

    $server = new Server(['name' => 'Demo Server']);
    $server->id = 100;

    $template = new Template(['name' => 'Ubuntu 22.04']);
    $template->id = 200;

    $jobs = buildJobsFor(new ServerDeploymentData(
        server: $server,
        template: $template,
        account_password: null,
        should_create_server: true,
        start_on_completion: false,
    ));

    expect($jobs[array_key_last($jobs)])->not->toBeInstanceOf(SendServerCredentialsEmailJob::class);
});
