<?php

use App\Actions\Auth\ConfigureCeremonyStepManagerFactoryAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * The localhost dev affordance must not narrow the origins the ceremony accepts.
 *
 * CheckAllowedOrigins treats a non-empty allowedOrigins list as an exhaustive
 * allowlist and stops matching the origin against the relying-party id entirely.
 * Setting it to ['localhost'] therefore made https://localhost the only origin
 * any local canary build would accept, so every passkey registration from the
 * real dev URL died with "Invalid origin. Not in the list of allowed origins."
 * — the credential was created on the authenticator but never stored.
 */
function readFactoryProperty(CeremonyStepManagerFactory $factory, string $property): mixed
{
    $reflection = new ReflectionProperty(CeremonyStepManagerFactory::class, $property);

    return $reflection->getValue($factory);
}

it('does not restrict allowed origins on a local canary build', function () {
    app()->detectEnvironment(fn () => 'local');
    config()->set('app.version', 'canary');

    $factory = (new ConfigureCeremonyStepManagerFactoryAction)->execute();

    // Empty (or null) keeps the default rp-id check, which accepts any origin
    // whose host matches the relying party — dev and production alike.
    expect(readFactoryProperty($factory, 'allowedOrigins'))->toBeEmpty();
});

it('marks localhost as a secured relying party on a local canary build', function () {
    app()->detectEnvironment(fn () => 'local');
    config()->set('app.version', 'canary');

    $factory = (new ConfigureCeremonyStepManagerFactoryAction)->execute();

    // This, not allowedOrigins, is what lets passkeys work over http://localhost:
    // it exempts that rp id from the ceremony's HTTPS requirement.
    expect(readFactoryProperty($factory, 'securedRelyingPartyId'))->toContain('localhost');
});

it('leaves the ceremony untouched outside a local canary build', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('app.version', 'canary');

    $factory = (new ConfigureCeremonyStepManagerFactoryAction)->execute();

    expect(readFactoryProperty($factory, 'allowedOrigins'))->toBeEmpty()
        ->and(readFactoryProperty($factory, 'securedRelyingPartyId'))->toBeEmpty();
});
