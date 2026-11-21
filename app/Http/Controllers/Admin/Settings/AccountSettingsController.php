<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\AccountSettingsData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Settings\UpdateAccountSettingsRequest;
use App\Services\Users\AccountPolicyResolver;
use App\Settings\AccountSettings;

class AccountSettingsController
{
    public function show(AccountSettings $settings, AccountPolicyResolver $resolver): AccountSettingsData
    {
        return $this->present($settings, $resolver);
    }

    public function update(
        UpdateAccountSettingsRequest $request,
        AccountSettings $settings,
        AccountPolicyResolver $resolver,
    ): AccountSettingsData {
        $settings->allow_name_change = $request->boolean('allow_name_change');
        $settings->allow_email_change = $request->boolean('allow_email_change');
        $settings->allow_password_change = $request->boolean('allow_password_change');
        $settings->allow_avatar_change = $request->boolean('allow_avatar_change');
        $settings->allow_password_reset = $request->boolean('allow_password_reset');
        $settings->send_server_ready_email = $request->boolean('send_server_ready_email');

        $settings->save();

        // No subject: this is panel-wide configuration, not an action on a record.
        Audit::record(
            AuditEvent::ADMIN_SETTINGS_ACCOUNT_UPDATED,
            properties: [
                'allow_name_change' => $settings->allow_name_change,
                'allow_email_change' => $settings->allow_email_change,
                'allow_password_change' => $settings->allow_password_change,
                'allow_avatar_change' => $settings->allow_avatar_change,
                'allow_password_reset' => $settings->allow_password_reset,
                'send_server_ready_email' => $settings->send_server_ready_email,
            ],
        );

        return $this->present($settings, $resolver);
    }

    /**
     * Read back through the resolver so this screen and the enforcement path
     * can never disagree about what the stored policy means. The two email
     * switches have no per-user resolution, so they are read as stored.
     */
    private function present(AccountSettings $settings, AccountPolicyResolver $resolver): AccountSettingsData
    {
        $policy = $resolver->global();

        return new AccountSettingsData(
            allowNameChange: $policy->canChangeName,
            allowEmailChange: $policy->canChangeEmail,
            allowPasswordChange: $policy->canChangePassword,
            allowAvatarChange: $policy->canChangeAvatar,
            allowPasswordReset: $settings->allow_password_reset,
            sendServerReadyEmail: $settings->send_server_ready_email,
        );
    }
}
