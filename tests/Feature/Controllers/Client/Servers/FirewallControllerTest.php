<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * The rules Proxmox is pretending to hold for these tests. Position is the
 * rule's identity, so the fixture is ordered and its indices matter.
 */
function fakeFirewallRules(): array
{
    return [
        [
            'pos' => 0,
            'type' => 'in',
            'action' => 'ACCEPT',
            'enable' => 1,
            'proto' => 'tcp',
            'dport' => '22',
            'source' => '+trusted',
            'comment' => 'SSH from office',
        ],
        [
            'pos' => 1,
            'type' => 'out',
            'action' => 'REJECT',
            'enable' => 0,
            'proto' => 'tcp',
            'dport' => '25',
        ],
    ];
}

function fakeFirewallHttp(array $overrides = []): void
{
    // Union rather than array_merge: overrides must both win on a duplicate
    // pattern and stay ahead of the '*' catch-all, which Http::fake would
    // otherwise match first.
    Http::fake($overrides + [
        '*/firewall/rules' => Http::response(['data' => fakeFirewallRules()], 200),
        '*/firewall/options' => Http::response(['data' => [
            'enable' => 1,
            'ipfilter' => 1,
            'policy_in' => 'DROP',
            'policy_out' => 'ACCEPT',
            'log_level_in' => 'info',
        ]], 200),
        '*/firewall/refs' => Http::response(['data' => [
            ['type' => 'ipset', 'name' => 'trusted', 'ref' => '+trusted', 'scope' => 'dc'],
        ]], 200),
        '*/cluster/firewall/macros' => Http::response(['data' => [
            ['macro' => 'SSH', 'descr' => 'Secure Shell traffic'],
        ]], 200),
        // Trailing wildcard: this one is always called with a query string.
        '*/firewall/log*' => Http::response(['data' => [
            ['n' => 1, 't' => '103 6 tap103i0-IN 16/Aug/2026:09:14:22 -0400 policy DROP: IN=fwbr103i0 OUT=fwbr103i0 SRC=45.83.64.12 DST=10.0.10.24 LEN=60 TTL=51 PROTO=TCP SPT=51422 DPT=22 SYN'],
        ]], 200),
        '*' => Http::response(['data' => null], 200),
    ]);
}

function testListFirewallRules(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure {
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create(['root_admin' => $secondUserIsAdmin]);
        }

        $response = $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/firewall/rules",
        );

        if ($useSecondUser && ! $secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.direction', 'in')
            ->assertJsonPath('data.0.action', 'ACCEPT')
            ->assertJsonPath('data.0.destinationPort', '22')
            ->assertJsonPath('data.0.sourceAddress', '+trusted')
            // Rule-level `enable` is an integer on the wire and a bool here.
            ->assertJsonPath('data.0.isEnabled', true)
            ->assertJsonPath('data.1.isEnabled', false);
    };
}

function testCreateFirewallRule(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure {
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create(['root_admin' => $secondUserIsAdmin]);
        }

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/firewall/rules",
            [
                'direction' => 'in',
                'action' => 'ACCEPT',
                'enabled' => true,
                'protocol' => 'tcp',
                'destination_port' => '443',
                'comment' => 'Public web',
            ],
        );

        if ($useSecondUser && ! $secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertNoContent();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/firewall/rules') || $request->method() !== 'POST') {
                return false;
            }

            return $request['type'] === 'in'
                && $request['action'] === 'ACCEPT'
                && $request['proto'] === 'tcp'
                && $request['dport'] === '443'
                // Sent as an integer, never a boolean.
                && $request['enable'] === 1;
        });

        // Proxmox always prepends, so the rule is moved to the end afterwards.
        // The fixture holds two rules, so the new one should end up at index 2
        // -- and `moveto` counts slots BEFORE the move, hence 3.
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/firewall/rules/0')
            && $request['moveto'] === 3);
    };
}

function testDeleteFirewallRule(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure {
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create(['root_admin' => $secondUserIsAdmin]);
        }

        $response = $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/1",
        );

        if ($useSecondUser && ! $secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertNoContent();

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/firewall/rules/1'));
    };
}

it('can list firewall rules', testListFirewallRules());
it('can create a firewall rule', testCreateFirewallRule());
it('can delete a firewall rule', testDeleteFirewallRule());

describe('admin', function () {
    it('can list firewall rules', testListFirewallRules(true, true));
    it('can create a firewall rule', testCreateFirewallRule(true, true));
    it('can delete a firewall rule', testDeleteFirewallRule(true, true));
});

describe('unauthorized users', function () {
    it("can't list firewall rules", testListFirewallRules(true));
    it("can't create a firewall rule", testCreateFirewallRule(true));
    it("can't delete a firewall rule", testDeleteFirewallRule(true));
});

describe('rules', function () {
    it('names dropped fields in the delete list rather than omitting them', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        // Rule 0 has a comment and a source; this update keeps neither.
        $response = $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/0",
            [
                'direction' => 'in',
                'action' => 'ACCEPT',
                'enabled' => true,
                'protocol' => 'tcp',
                'destination_port' => '22',
            ],
        );

        $response->assertNoContent();

        Http::assertSent(function ($request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/firewall/rules/0')) {
                return false;
            }

            $deleted = explode(',', $request['delete'] ?? '');

            return in_array('comment', $deleted, true)
                && in_array('source', $deleted, true);
        });
    });

    it('moves a rule without sending any other field', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/1/move",
            ['position' => 0],
        )->assertNoContent();

        Http::assertSent(function ($request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/firewall/rules/1')) {
                return false;
            }

            // Proxmox ignores everything else when moveto is present, so
            // sending anything alongside it would silently do nothing.
            return $request['moveto'] === 0 && count($request->data()) === 1;
        });
    });

    it('offsets a downward move to account for the rule leaving its own slot', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        // Rule 0 to the bottom of a two-rule set: the user means index 1, but
        // `moveto` is read against the list before the rule is lifted out.
        $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/0/move",
            ['position' => 1],
        )->assertNoContent();

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/firewall/rules/0')
            && $request['moveto'] === 2);
    });

    it('404s on a position that does not exist', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/99",
        )->assertNotFound();
    });

    it('rejects a macro combined with a protocol', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/firewall/rules",
            [
                'direction' => 'in',
                'action' => 'ACCEPT',
                'enabled' => true,
                'macro' => 'SSH',
                'protocol' => 'tcp',
            ],
        )->assertJsonValidationErrorFor('macro');
    });

    it('rejects an icmp type on a non-icmp rule', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/firewall/rules",
            [
                'direction' => 'in',
                'action' => 'ACCEPT',
                'enabled' => true,
                'protocol' => 'tcp',
                'icmp_type' => 'echo-request',
            ],
        )->assertJsonValidationErrorFor('icmp_type');
    });
});

describe('options', function () {
    it('applies Proxmox defaults to options the response omits', function () {
        fakeFirewallHttp([
            // A firewall that has never been configured returns almost nothing.
            '*/firewall/options' => Http::response(['data' => ['enable' => 1]], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/firewall/options",
        )
            ->assertOk()
            // Absent means ACCEPT, not null -- the firewall really is accepting.
            ->assertJsonPath('data.inboundPolicy', 'ACCEPT')
            ->assertJsonPath('data.outboundPolicy', 'ACCEPT')
            ->assertJsonPath('data.inboundLogLevel', 'nolog')
            ->assertJsonPath('data.hasIpFilter', false);
    });

    it('never writes the Convoy-managed options', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/options",
            [
                'inbound_policy' => 'DROP',
                'outbound_policy' => 'ACCEPT',
                'inbound_log_level' => 'info',
                'outbound_log_level' => 'nolog',
                // Both are platform guarantees; a user must not be able to
                // turn the firewall or the anti-spoof filter off from here.
                'enable' => false,
                'ipfilter' => false,
            ],
        )->assertOk();

        Http::assertSent(function ($request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/firewall/options')) {
                return false;
            }

            return $request['policy_in'] === 'DROP'
                && ! array_key_exists('enable', $request->data())
                && ! array_key_exists('ipfilter', $request->data());
        });
    });
});

describe('rule concurrency', function () {
    it('sends the digest on a delete so a stale position cannot hit the wrong rule', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/0",
            ['digest' => 'ruleset-v1'],
        )->assertNoContent();

        // In the query string, not the body: Proxmox rejects a DELETE that
        // carries content at all.
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/firewall/rules/0')
            && str_contains($request->url(), 'digest=ruleset-v1')
            && $request->data() === []);
    });

    it('turns a rejected stale delete into a conflict', function () {
        fakeFirewallHttp([
            // The GET that resolves the position succeeds; the DELETE does not.
            '*/firewall/rules/*' => Http::response(
                ['data' => null, 'errors' => 'detected modified configuration - file changed by other user'],
                500,
            ),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/firewall/rules/0",
            ['digest' => 'ruleset-v1'],
        )->assertStatus(409);
    });
});

describe('options concurrency', function () {
    it('round-trips the digest so a stale edit is rejected', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/options",
            [
                'inbound_policy' => 'DROP',
                'outbound_policy' => 'ACCEPT',
                'inbound_log_level' => 'nolog',
                'outbound_log_level' => 'nolog',
                'digest' => 'abc123',
            ],
        )->assertOk();

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/firewall/options')
            && $request['digest'] === 'abc123');
    });

    it('reports a digest mismatch as a conflict rather than a raw Proxmox error', function () {
        fakeFirewallHttp([
            // The PUT is the first call this pattern sees, so it takes the
            // refusal; the read-back after it never happens.
            '*/firewall/options' => Http::sequence()
                ->push(['data' => null, 'errors' => 'detected modified configuration - file changed by other user'], 500)
                ->push(['data' => ['enable' => 1, 'digest' => 'def456']], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->putJson(
            "/api/client/servers/{$server->uuid}/firewall/options",
            [
                'inbound_policy' => 'DROP',
                'outbound_policy' => 'ACCEPT',
                'inbound_log_level' => 'nolog',
                'outbound_log_level' => 'nolog',
                'digest' => 'abc123',
            ],
        )->assertStatus(409);
    });
});

describe('log', function () {
    it('drops the sentinel Proxmox returns instead of an empty list', function () {
        // A firewall with logging off answers with one line reading
        // "no content" -- rendering that verbatim would look like an entry.
        fakeFirewallHttp([
            '*/firewall/log*' => Http::response(
                ['data' => [['n' => 1, 't' => 'no content']]],
                200,
            ),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/firewall/log",
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('parses a raw iptables line into readable fields', function () {
        fakeFirewallHttp();

        [$user, $_, $_, $server] = createServerModel();

        $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/firewall/log",
        )
            ->assertOk()
            ->assertJsonPath('data.0.action', 'DROP')
            ->assertJsonPath('data.0.direction', 'in')
            ->assertJsonPath('data.0.sourceAddress', '45.83.64.12')
            ->assertJsonPath('data.0.destinationAddress', '10.0.10.24')
            ->assertJsonPath('data.0.destinationPort', 22)
            ->assertJsonPath('data.0.protocol', 'tcp');
    });
});
