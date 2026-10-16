<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\PermissionSettingsData;
use App\Enums\Audit\AuditEvent;
use App\Enums\User\UserType;
use App\Facades\Audit;
use App\Http\Requests\Admin\Settings\UpdatePermissionSettingsRequest;
use App\Models\User;
use App\Settings\PermissionSettings;

class PermissionSettingsController
{
    public function show(PermissionSettings $settings): PermissionSettingsData
    {
        return $this->present($settings);
    }

    public function update(
        UpdatePermissionSettingsRequest $request,
        PermissionSettings $settings,
    ): PermissionSettingsData {
        $settings->allow_guest_accounts = $request->boolean('allow_guest_accounts');
        $settings->save();

        // No subject: this is panel-wide configuration, not an action on a record.
        Audit::record(
            AuditEvent::ADMIN_SETTINGS_PERMISSIONS_UPDATED,
            properties: ['allow_guest_accounts' => $settings->allow_guest_accounts],
        );

        return $this->present($settings);
    }

    private function present(PermissionSettings $settings): PermissionSettingsData
    {
        return new PermissionSettingsData(
            allowGuestAccounts: $settings->allow_guest_accounts,
            guestAccountCount: User::query()->where('type', '=', UserType::GUEST)->count(),
        );
    }
}
