<?php

use App\Models\Passkey;
use App\Support\Passkeys\AuthenticatorAaguids;
use Illuminate\Support\Facades\Http;

/** An unencrypted JWT, which is how the FIDO Alliance serves its metadata blob. */
function fidoBlob(array $entries): string
{
    $segment = fn (array $payload) => rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

    return $segment(['alg' => 'none']).'.'.$segment(['entries' => $entries]).'.signature';
}

function fakeUpstream(array $fido, array $providers): void
{
    Http::fake([
        'mds3.fidoalliance.org/*' => Http::response(fidoBlob($fido)),
        'raw.githubusercontent.com/*' => Http::response($providers === [] ? '{}' : json_encode($providers)),
    ]);
}

/** Reads back what the command wrote, bypassing the class's memoised copy. */
function generatedTable(): array
{
    return eval('?>'.file_get_contents(AuthenticatorAaguids::TABLE));
}

beforeEach(function () {
    $this->table = file_get_contents(AuthenticatorAaguids::TABLE);
});

afterEach(function () {
    file_put_contents(AuthenticatorAaguids::TABLE, $this->table);
    AuthenticatorAaguids::forget();
});

it('merges both sources, letting the passkey provider list win', function () {
    fakeUpstream(
        fido: [
            ['aaguid' => 'cb69481e-8ff7-4039-93ec-0a2729a154a8', 'metadataStatement' => ['description' => 'YubiKey 5 Series']],
            // The FIDO blob describes a handful of software providers too, in its own terms.
            ['aaguid' => 'd548826e-79b4-db40-a3d8-11116f7e8349', 'metadataStatement' => ['description' => 'Bitwarden Password Manager Authenticator']],
        ],
        providers: ['D548826E-79B4-DB40-A3D8-11116F7E8349' => ['name' => 'Bitwarden']],
    );

    $this->artisan('maintenance:refresh-aaguids')->assertSuccessful();

    expect(generatedTable())->toBe([
        'd548826e-79b4-db40-a3d8-11116f7e8349' => 'Bitwarden',
        'cb69481e-8ff7-4039-93ec-0a2729a154a8' => 'YubiKey 5 Series',
    ]);
});

it('writes a table the class can read back', function () {
    fakeUpstream(fido: [], providers: ['bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password']]);

    $this->artisan('maintenance:refresh-aaguids')->assertSuccessful();

    expect(generatedTable())->toBe(['bada5566-a7aa-401f-bd96-45619a55120d' => '1Password']);
});

it('reports what changed', function () {
    // Its own baseline, so the report is about these three entries and not whatever the committed
    // table happens to hold today.
    file_put_contents(AuthenticatorAaguids::TABLE, <<<'PHP'
        <?php

        return [
            'd548826e-79b4-db40-a3d8-11116f7e8349' => 'Bitwarden',
            '11111111-1111-1111-1111-111111111111' => 'Retired Vault',
        ];
        PHP);
    AuthenticatorAaguids::forget();

    fakeUpstream(fido: [], providers: [
        'bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password'],
        'd548826e-79b4-db40-a3d8-11116f7e8349' => ['name' => 'Bitwarden Password Manager'],
    ]);

    $this->artisan('maintenance:refresh-aaguids')
        ->expectsOutputToContain('+ 1Password (bada5566-a7aa-401f-bd96-45619a55120d)')
        ->expectsOutputToContain('- Retired Vault (11111111-1111-1111-1111-111111111111)')
        ->expectsOutputToContain('~ Bitwarden -> Bitwarden Password Manager (d548826e-79b4-db40-a3d8-11116f7e8349)')
        ->assertSuccessful();
});

it('reports when nothing changed', function () {
    file_put_contents(AuthenticatorAaguids::TABLE, <<<'PHP'
        <?php

        return [
            'bada5566-a7aa-401f-bd96-45619a55120d' => '1Password',
        ];
        PHP);
    AuthenticatorAaguids::forget();

    fakeUpstream(fido: [], providers: ['bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password']]);

    $this->artisan('maintenance:refresh-aaguids')
        ->expectsOutputToContain('No changes.')
        ->assertSuccessful();
});

it('leaves the table alone on a dry run', function () {
    fakeUpstream(fido: [], providers: ['bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password']]);

    $this->artisan('maintenance:refresh-aaguids', ['--dry-run' => true])->assertSuccessful();

    expect(file_get_contents(AuthenticatorAaguids::TABLE))->toBe($this->table);
});

it('refuses to overwrite the table when a source is unreachable', function () {
    Http::fake([
        'mds3.fidoalliance.org/*' => Http::response(status: 429),
        'raw.githubusercontent.com/*' => Http::response('{}'),
    ]);

    $this->artisan('maintenance:refresh-aaguids')->assertFailed();

    expect(file_get_contents(AuthenticatorAaguids::TABLE))->toBe($this->table);
});

it('refuses to overwrite the table when both sources come back empty', function () {
    fakeUpstream(fido: [], providers: []);

    $this->artisan('maintenance:refresh-aaguids')->assertFailed();

    expect(file_get_contents(AuthenticatorAaguids::TABLE))->toBe($this->table);
});

it('strips qualifiers that mean nothing to an end user', function (string $raw, string $expected) {
    expect(AuthenticatorAaguids::displayName($raw))->toBe($expected);
})->with([
    'profile' => ['YubiKey 5 Series with NFC (Enterprise Profile)', 'YubiKey 5 Series with NFC'],
    'preview' => ['YubiKey 5 FIPS Series with NFC Preview', 'YubiKey 5 FIPS Series with NFC'],
    'rc preview' => ['YubiKey 5 FIPS Series (RC Preview)', 'YubiKey 5 FIPS Series'],
    'ctap versions' => ['Feitian ePass FIDO-NFC (CTAP2.1, CTAP2.0, U2F)', 'Feitian ePass FIDO-NFC'],
    'draft' => ['IDEMIA SOLVO Fly 80 R1 FIDO Card Draft', 'IDEMIA SOLVO Fly 80 R1 FIDO Card'],
    'batch code' => ['YubiKey 5 Series with NFC KVZR57-2', 'YubiKey 5 Series with NFC'],
    'stray whitespace' => ['  TOKEN2 FIDO2  Security Key ', 'TOKEN2 FIDO2 Security Key'],
    'nothing to strip' => ['1Password', '1Password'],
    // A model number is not a batch code, and losing it would merge distinct authenticators.
    'keeps a real model number' => ['Google Titan Security Key v2', 'Google Titan Security Key v2'],
]);

it('trims an over-long name on a word boundary', function () {
    $name = AuthenticatorAaguids::displayName('SECORA Connect SLS21 D1 FIDO 2.1 v1.0 by Infineon Technologies');

    expect($name)->toBe('SECORA Connect SLS21 D1 FIDO 2.1 v1.0')
        ->and(mb_strlen($name))->toBeLessThanOrEqual(Passkey::NAME_MAX_LENGTH);
});
