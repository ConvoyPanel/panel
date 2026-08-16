<?php

namespace App\Console\Commands\Maintenance;

use App\Support\Passkeys\AuthenticatorAaguids;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Regenerates the AAGUID lookup table {@see AuthenticatorAaguids} reads, by merging the two
 * upstream sources that between them cover the authenticators people actually use.
 *
 * This is a development-time command, not something an install runs. It rewrites a file in the
 * source tree, and the intent is that whoever runs it reviews the diff before committing — which
 * is also the reason it doesn't bother verifying the FIDO blob's signature. The table only decides
 * a default display name, and a human reads every change to it.
 *
 * Upstream adds a handful of entries a month, almost always niche vaults, so this rarely needs
 * running. Anything missing just falls back to a datestamped name.
 */
class RefreshAuthenticatorAaguidsCommand extends Command
{
    /**
     * The FIDO Alliance Metadata Service blob: hardware security keys and platform authenticators,
     * described for auditors. Served as an unencrypted JWT whose payload holds the entries.
     */
    private const FIDO_MDS_URL = 'https://mds3.fidoalliance.org/';

    /**
     * The community list of software passkey providers — 1Password, Bitwarden, iCloud Keychain,
     * Windows Hello and friends. Almost none of these are in the FIDO blob, and they are the
     * names a person is most likely to see, so they win on conflict.
     */
    private const PASSKEY_PROVIDERS_URL = 'https://raw.githubusercontent.com/passkeydeveloper/passkey-authenticator-aaguids/main/aaguid.json';

    /** Upstream placeholders that are not anyone's authenticator. */
    private const REJECT = ['initial'];

    /**
     * @var string
     */
    protected $signature = 'maintenance:refresh-aaguids {--dry-run : Report what would change without writing the table.}';

    /**
     * @var string
     */
    protected $description = 'Regenerates the passkey authenticator name table from the FIDO Metadata Service and the community passkey provider list.';

    public function handle(): int
    {
        try {
            // The FIDO blob is the base layer; the community list overwrites it, so its more
            // recognisable names win wherever both describe the same authenticator.
            $names = [...$this->fetchFidoMetadata(), ...$this->fetchPasskeyProviders()];
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $names = array_filter(
            $names,
            fn (string $name) => $name !== '' && ! in_array($name, self::REJECT, true),
        );

        if ($names === []) {
            $this->error('Both sources came back empty; refusing to overwrite the table.');

            return self::FAILURE;
        }

        // Sort by name so the generated file diffs readably: a new authenticator shows up as one
        // added line next to its siblings, rather than wherever its AAGUID happens to sort.
        uksort($names, fn (string $a, string $b) => [mb_strtolower($names[$a]), $a] <=> [mb_strtolower($names[$b]), $b]);

        // Not a given: the very first run generates the table from nothing.
        AuthenticatorAaguids::forget();
        $this->reportChanges(file_exists(AuthenticatorAaguids::TABLE) ? AuthenticatorAaguids::names() : [], $names);

        if ($this->option('dry-run')) {
            $this->comment('Dry run — the table was left alone.');

            return self::SUCCESS;
        }

        file_put_contents(AuthenticatorAaguids::TABLE, $this->render($names));
        AuthenticatorAaguids::forget();

        $this->info(count($names).' authenticators written to '.AuthenticatorAaguids::TABLE.'.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function fetchFidoMetadata(): array
    {
        $blob = $this->get(self::FIDO_MDS_URL);

        // An unencrypted JWT: header.payload.signature, each base64url. We only want the payload,
        // and deliberately don't verify the signature — see the class docblock.
        $segments = explode('.', trim($blob));

        if (count($segments) !== 3) {
            throw new RuntimeException('The FIDO metadata blob was not a JWT.');
        }

        $payload = json_decode(
            base64_decode(strtr($segments[1], '-_', '+/'), true) ?: '',
            associative: true,
        );

        if (! is_array($payload['entries'] ?? null)) {
            throw new RuntimeException('The FIDO metadata blob carried no entries.');
        }

        $names = [];

        foreach ($payload['entries'] as $entry) {
            // Entries for UAF/U2F authenticators carry no AAGUID; they can't produce a passkey.
            $aaguid = $entry['aaguid'] ?? null;
            $description = $entry['metadataStatement']['description'] ?? null;

            if (is_string($aaguid) && is_string($description)) {
                $names[mb_strtolower($aaguid)] = AuthenticatorAaguids::displayName($description);
            }
        }

        $this->line(count($names).' authenticators from the FIDO Metadata Service.');

        return $names;
    }

    /**
     * @return array<string, string>
     */
    private function fetchPasskeyProviders(): array
    {
        $providers = json_decode($this->get(self::PASSKEY_PROVIDERS_URL), associative: true);

        if (! is_array($providers) || $providers === []) {
            throw new RuntimeException('The passkey provider list could not be read.');
        }

        $names = [];

        foreach ($providers as $aaguid => $provider) {
            if (is_string($provider['name'] ?? null)) {
                $names[mb_strtolower($aaguid)] = AuthenticatorAaguids::displayName($provider['name']);
            }
        }

        $this->line(count($names).' authenticators from the community passkey provider list.');

        return $names;
    }

    private function get(string $url): string
    {
        $response = Http::timeout(60)->get($url);

        if ($response->failed()) {
            throw new RuntimeException("Could not fetch {$url} (HTTP {$response->status()}).");
        }

        return $response->body();
    }

    /**
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     */
    private function reportChanges(array $before, array $after): void
    {
        foreach (array_diff_key($after, $before) as $aaguid => $name) {
            $this->info("  + {$name} ({$aaguid})");
        }

        foreach (array_diff_key($before, $after) as $aaguid => $name) {
            $this->warn("  - {$name} ({$aaguid})");
        }

        foreach (array_intersect_key($before, $after) as $aaguid => $name) {
            if ($after[$aaguid] !== $name) {
                $this->comment("  ~ {$name} -> {$after[$aaguid]} ({$aaguid})");
            }
        }

        if ($before === $after) {
            $this->line('No changes.');
        }
    }

    /**
     * @param  array<string, string>  $names
     */
    private function render(array $names): string
    {
        $rows = '';

        foreach ($names as $aaguid => $name) {
            $rows .= sprintf("    '%s' => '%s',\n", $aaguid, str_replace(['\\', "'"], ['\\\\', "\\'"], $name));
        }

        return <<<PHP
            <?php

            /*
             * Generated by `php artisan {$this->getName()}` — do not edit by hand.
             *
             * Maps a WebAuthn authenticator's AAGUID onto a name worth showing a person. Read
             * through App\\Support\\Passkeys\\AuthenticatorAaguids, which is where the surrounding
             * behaviour (and the reason this is vendored rather than fetched) is documented.
             *
             * An AAGUID only looks like a UUID — it is 16 opaque bytes, so several of these keys
             * are not valid RFC 4122 (Proton Pass's is the ASCII "ProtonPassProton").
             */

            return [
            {$rows}];

            PHP;
    }
}
