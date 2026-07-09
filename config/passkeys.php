<?php

use App\Actions\Auth\ConfigureCeremonyStepManagerFactoryAction;
use App\Actions\Auth\StorePasskeyAction;
use App\Models\Passkey;
use App\Models\User;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;

return [
    /*
     * After a successful authentication attempt using a passkey
     * we'll redirect to this URL.
     *
     * Convoy drives passkey auth over JSON endpoints and performs its own
     * redirects client-side, so this is only a fallback default.
     */
    'redirect_to_after_login' => '/',

    /*
     * These classes are responsible for performing core tasks regarding passkeys.
     * We point the two that need Convoy-specific behavior at thin subclasses:
     *   - store_passkey: keeps our curated error-code exceptions (HasErrorCode).
     *   - configure_ceremony_step_manager_factory: canary/localhost origin handling.
     * The rest use the package defaults.
     */
    'actions' => [
        'generate_passkey_register_options' => GeneratePasskeyRegisterOptionsAction::class,
        'store_passkey' => StorePasskeyAction::class,
        'generate_passkey_authentication_options' => GeneratePasskeyAuthenticationOptionsAction::class,
        'find_passkey' => FindPasskeyToAuthenticateAction::class,
        'configure_ceremony_step_manager_factory' => ConfigureCeremonyStepManagerFactoryAction::class,
    ],

    /*
     * These properties will be used to generate the passkey.
     */
    'relying_party' => [
        'name' => config('app.name'),
        'id' => parse_url(config('app.url'), PHP_URL_HOST),
        'icon' => null,
    ],

    /*
     * The models used by the package.
     *
     * `passkey` points at our thin subclass that keeps the existing `user_id`
     * schema (via User::passkeys()) and the `id` route key.
     */
    'models' => [
        'passkey' => Passkey::class,
        'authenticatable' => User::class,
    ],
];
