<?php

namespace App\Services\Servers;

use App\Enums\Server\DeploymentType;
use App\Models\Deployment;
use App\Notifications\ServerReady;
use App\Services\Mail\MailConfigurator;
use App\Settings\AccountSettings;

/**
 * Decides whether a finished deployment is worth an email to the server's owner.
 *
 * Only the deployment that brought the server into existence counts. A reinstall is something
 * the owner started and is usually watching, and a migration changes nothing they can reach, so
 * an email for either would only be noise.
 */
class ServerReadyNotifier
{
    public function __construct(
        private AccountSettings $settings,
        private MailConfigurator $mail,
    ) {}

    public function deploymentCompleted(Deployment $deployment): void
    {
        if (! in_array($deployment->type, [DeploymentType::INSTALL, DeploymentType::IMPORT], true)) {
            return;
        }

        // Without a relay the queued message would fail on a worker with nobody watching, so it
        // is not sent at all, the same as an invite.
        if (! $this->settings->send_server_ready_email || ! $this->mail->isConfigured()) {
            return;
        }

        $server = $deployment->server;

        $server->user->notify(new ServerReady($server));
    }
}
