<?php

namespace App\Services\Provisioners;

use App\Models\Provisioner;
use Illuminate\Support\Collection;

/**
 * The cloud-init vendor data that carries a server's provisioner scripts.
 *
 * Every file goes in base64. The only YAML written here is fixed keys and
 * fixed paths, so a script or a field value can never be read as YAML or as
 * shell: the scripts get the values from the environment, as
 * {@see ProvisionerEnvironment} builds it, and nowhere else.
 */
class ProvisionerSnippet
{
    public const DIRECTORY = '/var/lib/convoy/provisioners';

    /**
     * @param  Collection<int, Provisioner>  $provisioners  in the order they run
     * @param  array<string, string>  $environment
     */
    public function build(
        Collection $provisioners,
        array $environment,
        string $callbackUrl,
        string $token,
    ): string {
        $dir = self::DIRECTORY;

        $files = [
            ["{$dir}/run", '0700', (string) file_get_contents(resource_path('provisioners/run.sh'))],
            ["{$dir}/environment", '0600', $this->environmentFile($environment)],
            ["{$dir}/callback", '0600', "{$callbackUrl}\n{$token}\n"],
            ["{$dir}/order", '0600', $provisioners->map(fn (Provisioner $p) => $p->uuid."\n")->implode('')],
        ];

        foreach ($provisioners as $provisioner) {
            $files[] = ["{$dir}/scripts/{$provisioner->uuid}", '0700', $this->normaliseLineEndings($provisioner->script)];
        }

        $yaml = "#cloud-config\n";
        $yaml .= "# Written by Convoy. The node keeps this file only until the scripts report back.\n";
        $yaml .= "write_files:\n";

        foreach ($files as [$path, $permissions, $contents]) {
            $yaml .= "  - path: {$path}\n";
            $yaml .= "    permissions: '{$permissions}'\n";
            $yaml .= "    encoding: b64\n";
            $yaml .= '    content: '.base64_encode($contents)."\n";
        }

        $yaml .= "runcmd:\n";
        $yaml .= "  - [{$dir}/run]\n";

        return $yaml;
    }

    /**
     * One NAME=value per line, which the runner exports without evaluating.
     *
     * A line break inside a value would start a new variable, so control
     * characters are replaced. Field values are validated to have none; this is
     * for the server's own details, which are checked less strictly.
     *
     * @param  array<string, string>  $environment
     */
    private function environmentFile(array $environment): string
    {
        $lines = '';

        foreach ($environment as $name => $value) {
            $lines .= $name.'='.preg_replace('/\p{Cc}/u', ' ', $value)."\n";
        }

        return $lines;
    }

    /**
     * A script pasted from a Windows editor would otherwise fail on its
     * shebang line with `/bin/bash^M: bad interpreter`.
     */
    private function normaliseLineEndings(string $script): string
    {
        return str_replace(["\r\n", "\r"], "\n", $script);
    }
}
