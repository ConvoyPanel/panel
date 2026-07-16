<?php

use App\Actions\Auth\GeneratePasskeyAuthenticationOptionsAction;
use App\Actions\Auth\GeneratePasskeyRegisterOptionsAction;
use App\Models\User;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

it('requires user verification when authenticating with a passkey', function () {
    $options = json_decode(
        app(GeneratePasskeyAuthenticationOptionsAction::class)->execute(),
        true,
    );

    expect($options['userVerification'])
        ->toBe(PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED);
});

it('requires user verification when registering a passkey', function () {
    $options = app(GeneratePasskeyRegisterOptionsAction::class)->execute(
        User::factory()->create(),
        asJson: false,
    );

    expect($options->authenticatorSelection->userVerification)
        ->toBe(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED)
        ->and($options->authenticatorSelection->residentKey)
        ->toBe(AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED);
});
