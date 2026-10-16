<?php

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Facades\Http;

function proxmoxErrorFor(array|string $body, int $status = 500): ProxmoxConnectionException
{
    Http::fake(['*' => Http::response($body, $status)]);
    [$_, $_, $_, $server] = createServerModel();

    try {
        app(ProxmoxConfigRepository::class)->setServer($server)->getConfig();
    } catch (ProxmoxConnectionException $e) {
        return $e;
    }

    throw new RuntimeException('Expected a ProxmoxConnectionException');
}

// A logged Proxmox error used to print its stack trace three times: inside the
// message, for this exception, and for the RequestException chained under it.
it('logs one trace: no trace in the message, nothing chained', function () {
    $e = proxmoxErrorFor(['data' => null, 'message' => "Configuration file 'nodes/pve/qemu-server/109.conf' does not exist\n"]);

    expect($e->getMessage())
        ->toBe("Proxmox returned 500: Configuration file 'nodes/pve/qemu-server/109.conf' does not exist")
        ->not->toContain('#0 ')
        ->and($e->getPrevious())->toBeNull()
        ->and($e->response->status())->toBe(500);
});

it("falls back to the HTTP client's message when Proxmox sends no JSON", function () {
    $e = proxmoxErrorFor("can't connect to 10.0.0.1:443 (connection refused)", 595);

    expect($e->getMessage())
        ->toContain("can't connect to 10.0.0.1:443")
        ->not->toContain('#0 ');
});
