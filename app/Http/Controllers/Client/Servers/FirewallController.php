<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\Proxmox\Firewall\FirewallLogEntryData;
use App\Data\Server\Proxmox\Firewall\FirewallMacroData;
use App\Data\Server\Proxmox\Firewall\FirewallRefData;
use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\FirewallPolicy;
use App\Http\Requests\Client\Servers\Firewall\DeleteFirewallRuleRequest;
use App\Http\Requests\Client\Servers\Firewall\MoveFirewallRuleRequest;
use App\Http\Requests\Client\Servers\Firewall\StoreFirewallRuleRequest;
use App\Http\Requests\Client\Servers\Firewall\UpdateFirewallOptionsRequest;
use App\Http\Requests\Client\Servers\Firewall\UpdateFirewallRuleRequest;
use App\Models\Server;
use App\Services\Servers\ServerFirewallService;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

/**
 * The server's firewall, read and written straight through to Proxmox.
 *
 * Nothing here is persisted by Convoy: a rule's identity is its index in the
 * remote ruleset, which renumbers whenever anything is inserted, deleted, or
 * moved. Callers must treat every position as valid only until their next
 * write.
 */
class FirewallController
{
    public function __construct(
        private ServerFirewallService $firewallService,
    ) {}

    public function options(Server $server)
    {
        return $this->firewallService->getOptions($server);
    }

    public function updateOptions(UpdateFirewallOptionsRequest $request, Server $server)
    {
        return $this->firewallService->updateOptions(
            $server,
            $request->enum('inbound_policy', FirewallPolicy::class),
            $request->enum('outbound_policy', FirewallPolicy::class),
            $request->enum('inbound_log_level', FirewallLogLevel::class),
            $request->enum('outbound_log_level', FirewallLogLevel::class),
            $request->validated('digest'),
        );
    }

    public function index(Server $server)
    {
        return FirewallRuleData::collect(
            $this->firewallService->getRules($server),
            DataCollection::class,
        );
    }

    public function store(StoreFirewallRuleRequest $request, Server $server)
    {
        $this->firewallService->createRule(
            $server,
            $request->toRuleData(),
            $request->validated('position'),
        );

        return response()->noContent();
    }

    public function update(UpdateFirewallRuleRequest $request, Server $server, int $position)
    {
        $rule = $request->toRuleData();

        $this->firewallService->updateRule($server, $position, $rule, $rule->digest);

        return response()->noContent();
    }

    public function move(MoveFirewallRuleRequest $request, Server $server, int $position)
    {
        $this->firewallService->moveRule(
            $server,
            $position,
            $request->validated('position'),
            $request->validated('digest'),
        );

        return response()->noContent();
    }

    public function destroy(DeleteFirewallRuleRequest $request, Server $server, int $position)
    {
        $this->firewallService->deleteRule($server, $position, $request->validated('digest'));

        return response()->noContent();
    }

    public function refs(Server $server)
    {
        return FirewallRefData::collect(
            $this->firewallService->getRefs($server),
            DataCollection::class,
        );
    }

    public function macros(Server $server)
    {
        return FirewallMacroData::collect(
            $this->firewallService->getMacros($server),
            DataCollection::class,
        );
    }

    public function log(Request $request, Server $server)
    {
        return FirewallLogEntryData::collect(
            $this->firewallService->getLog(
                $server,
                max((int) $request->query('start', 0), 0),
                min(max((int) $request->query('limit', 100), 1), 500),
            ),
            DataCollection::class,
        );
    }
}
