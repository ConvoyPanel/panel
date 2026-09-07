<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\MailSettingsData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Mail\MailEncryption;
use App\Facades\Audit;
use App\Http\Requests\Admin\Settings\TestMailSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateMailSettingsRequest;
use App\Services\Mail\MailConfigurator;
use App\Settings\MailSettings;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class MailSettingsController
{
    public function show(MailSettings $settings, MailConfigurator $configurator): MailSettingsData
    {
        return $this->present($settings, $configurator);
    }

    public function update(
        UpdateMailSettingsRequest $request,
        MailSettings $settings,
        MailConfigurator $configurator,
    ): MailSettingsData {
        $host = trim((string) $request->input('host'));

        if ($host === '') {
            // Clearing the host clears the tier. Leaving a stale password encrypted in the
            // settings table for a relay the panel no longer talks to would be a credential
            // kept for no reason.
            $settings->host = '';
            $settings->username = '';
            $settings->password = '';
            $settings->from_address = '';
            $settings->from_name = '';
        } else {
            $settings->host = $host;
            $settings->port = (int) $request->input('port');
            $settings->username = (string) $request->input('username', '');
            $settings->encryption = $request->enum('encryption', MailEncryption::class);
            $settings->from_address = (string) $request->input('from_address');
            $settings->from_name = (string) $request->input('from_name');

            // Absent means keep; present-but-empty means clear. The screen omits the key
            // entirely when the admin does not touch the password field.
            if ($request->has('password')) {
                $settings->password = (string) $request->input('password', '');
            }
        }

        $settings->save();

        // The values, never the password — and never a "password" key at all, since even
        // recording that one was set alongside a host tells the log more than it needs.
        Audit::record(
            AuditEvent::ADMIN_SETTINGS_MAIL_UPDATED,
            properties: array_filter([
                'host' => $settings->host ?: null,
                'port' => $settings->host ? $settings->port : null,
                'encryption' => $settings->host ? $settings->encryption->value : null,
                'from_address' => $settings->from_address ?: null,
                'cleared' => $settings->host === '' ?: null,
            ], fn ($value) => $value !== null),
        );

        // Rebuild the runtime mailer so anything sending later in this request uses what was
        // just saved rather than the config this process booted with.
        $configurator->applyAndPurge();

        return $this->present($settings, $configurator);
    }

    /**
     * Send a test message using the submitted (not necessarily saved) settings.
     */
    public function test(
        TestMailSettingsRequest $request,
        MailSettings $settings,
        MailConfigurator $configurator,
    ) {
        // A password the screen never displayed cannot be retyped, so an omitted key falls
        // back to what is stored. That keeps "change the port and retest" a one-field edit.
        $password = $request->has('password')
            ? (string) $request->input('password', '')
            : $settings->password;

        $recipient = (string) ($request->input('recipient') ?: $request->user()->email);

        $encryption = $request->enum('encryption', MailEncryption::class);

        $transport = $configurator->buildTransportConfig(
            host: (string) $request->input('host'),
            port: (int) $request->input('port'),
            username: (string) $request->input('username', ''),
            password: $password,
            encryption: $encryption,
        );

        try {
            $configurator->sendTest(
                $recipient,
                $transport,
                (string) $request->input('from_address'),
                (string) $request->input('from_name'),
                $encryption,
            );
        } catch (Throwable $e) {
            // The transport's own message is the entire value of this endpoint — "Connection
            // could not be established", "535 Authentication failed", "550 sender rejected" are
            // each a different afternoon. Swallowing it for a tidy "test failed" would leave the
            // operator exactly where they were before this screen existed.
            Audit::record(
                AuditEvent::ADMIN_SETTINGS_MAIL_TESTED,
                properties: [
                    'host' => $request->input('host'),
                    'recipient' => $recipient,
                    'succeeded' => false,
                ],
            );

            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        Audit::record(
            AuditEvent::ADMIN_SETTINGS_MAIL_TESTED,
            properties: [
                'host' => $request->input('host'),
                'recipient' => $recipient,
                'succeeded' => true,
            ],
        );

        return response()->json([
            'data' => ['recipient' => $recipient],
        ]);
    }

    private function present(MailSettings $settings, MailConfigurator $configurator): MailSettingsData
    {
        return new MailSettingsData(
            host: $settings->host,
            port: $settings->port,
            username: $settings->username,
            encryption: $settings->encryption,
            fromAddress: $settings->from_address,
            fromName: $settings->from_name,
            passwordSet: $settings->password !== '',
            configured: $configurator->isConfigured(),
        );
    }
}
