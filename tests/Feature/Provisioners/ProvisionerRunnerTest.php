<?php

use App\Models\Provisioner;
use App\Services\Provisioners\ProvisionerSnippet;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * The runner the snippet installs, run under `sh` exactly as it ships.
 *
 * The only stand-in is `curl`, which is replaced on PATH by a script that
 * records what it was asked to send and answers 204.
 */
beforeEach(function () {
    $this->root = sys_get_temp_dir().'/convoy-runner-'.Str::random(8);
    File::ensureDirectoryExists("{$this->root}/bin");

    file_put_contents("{$this->root}/bin/curl", <<<'SH'
    #!/bin/sh
    out="$CURL_RECORD"
    : >"$out"
    while [ $# -gt 0 ]; do
        case "$1" in
            -H) printf 'header %s\n' "$2" >>"$out"; shift ;;
            --data) printf 'body %s\n' "$2" >>"$out"; shift ;;
            http*) printf 'url %s\n' "$1" >>"$out" ;;
        esac
        shift
    done
    printf 204
    SH);
    chmod("{$this->root}/bin/curl", 0755);
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

/**
 * Lay the snippet's files out under the test's root, the way cloud-init
 * write_files would under /.
 */
function installSnippet(string $root, string $snippet): string
{
    preg_match_all('/- path: (\S+)\n    permissions: \'(\d+)\'\n    encoding: b64\n    content: (\S+)/', $snippet, $matches, PREG_SET_ORDER);

    foreach ($matches as [, $path, $permissions, $content]) {
        $target = $root.$path;
        File::ensureDirectoryExists(dirname($target));
        file_put_contents($target, base64_decode($content));
        chmod($target, octdec($permissions));
    }

    return $root.ProvisionerSnippet::DIRECTORY;
}

function runRunner(string $root, string $dir): Process
{
    $process = new Process(['sh', "{$dir}/run"], null, [
        'PATH' => "{$root}/bin:/usr/bin:/bin",
        'CONVOY_PROVISIONER_DIR' => $dir,
        'CONVOY_PROVISIONER_LOG' => "{$root}/runner.log",
        'CURL_RECORD' => "{$root}/curl.txt",
        'OUT' => "{$root}/out",
    ]);
    $process->mustRun();

    return $process;
}

it('runs each script in order with its environment, stops at a failure, and reports', function () {
    $first = Provisioner::factory()->make(['uuid' => (string) Str::uuid(), 'script' => "#!/bin/sh\nprintf '%s' \"\$CONVOY_FIELD_DOMAIN\" >\"\$OUT.domain\"\nprintf '%s' \"\$CONVOY_HOSTNAME\" >\"\$OUT.hostname\"\necho first >>\"\$OUT.order\"\n"]);
    $second = Provisioner::factory()->make(['uuid' => (string) Str::uuid(), 'script' => "#!/bin/sh\necho second >>\"\$OUT.order\"\nexit 3\n"]);
    $third = Provisioner::factory()->make(['uuid' => (string) Str::uuid(), 'script' => "#!/bin/sh\necho third >>\"\$OUT.order\"\n"]);

    // Everything a shell would act on, if the value were ever evaluated.
    $hostile = 'a\'b"c $(touch '.$this->root.'/pwned) `touch '.$this->root.'/pwned` ${HOME} \\ ;|&';

    $snippet = app(ProvisionerSnippet::class)->build(
        collect([$first, $second, $third]),
        ['CONVOY_HOSTNAME' => 'web.example.com', 'CONVOY_FIELD_DOMAIN' => $hostile],
        'https://panel.example.com/api/provisioning/report',
        'the-token',
    );

    $dir = installSnippet($this->root, $snippet);
    runRunner($this->root, $dir);

    expect(file_get_contents("{$this->root}/out.domain"))->toBe($hostile)
        ->and(file_get_contents("{$this->root}/out.hostname"))->toBe('web.example.com')
        ->and(file_exists("{$this->root}/pwned"))->toBeFalse()
        ->and(file_get_contents("{$this->root}/out.order"))->toBe("first\nsecond\n");

    $sent = file_get_contents("{$this->root}/curl.txt");
    expect($sent)->toContain('url https://panel.example.com/api/provisioning/report')
        ->and($sent)->toContain('header Authorization: Bearer the-token');

    preg_match('/^body (.*)$/m', $sent, $body);
    expect(json_decode($body[1], true))->toBe(['results' => [
        ['provisioner' => $first->uuid, 'exit_code' => 0],
        ['provisioner' => $second->uuid, 'exit_code' => 3],
    ]]);

    // The values and the token do not outlive the run.
    expect(file_exists("{$dir}/environment"))->toBeFalse()
        ->and(file_exists("{$dir}/callback"))->toBeFalse()
        ->and(file_exists("{$dir}/done"))->toBeTrue();
});

it('does nothing the second time', function () {
    $once = Provisioner::factory()->make(['uuid' => (string) Str::uuid(), 'script' => "#!/bin/sh\necho ran >>\"\$OUT.order\"\n"]);

    $snippet = app(ProvisionerSnippet::class)->build(collect([$once]), [], 'https://panel.example.com/report', 't');
    $dir = installSnippet($this->root, $snippet);

    runRunner($this->root, $dir);
    unlink("{$this->root}/curl.txt");
    runRunner($this->root, $dir);

    expect(file_get_contents("{$this->root}/out.order"))->toBe("ran\n")
        ->and(file_exists("{$this->root}/curl.txt"))->toBeFalse();
});

it('fixes Windows line endings, which would break the shebang', function () {
    $crlf = Provisioner::factory()->make(['uuid' => (string) Str::uuid(), 'script' => "#!/bin/sh\r\necho ok >\"\$OUT.crlf\"\r\n"]);

    $snippet = app(ProvisionerSnippet::class)->build(collect([$crlf]), [], 'https://panel.example.com/report', 't');
    runRunner($this->root, installSnippet($this->root, $snippet));

    expect(file_get_contents("{$this->root}/out.crlf"))->toBe("ok\n");
});
