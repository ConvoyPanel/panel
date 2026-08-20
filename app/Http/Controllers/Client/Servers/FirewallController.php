<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\Proxmox\Firewall\FirewallLogEntryData;
use App\Data\Server\Proxmox\Firewall\FirewallMacroData;
use App\Data\Server\Proxmox\Firewall\FirewallRefData;
use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\FirewallPolicy;
use App\Facades\Audit;
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
        $inboundPolicy = $request->enum('inbound_policy', FirewallPolicy::class);
        $outboundPolicy = $request->enum('outbound_policy', FirewallPolicy::class);

        $options = $this->firewallService->updateOptions(
            $server,
            $inboundPolicy,
            $outboundPolicy,
            $request->enum('inbound_log_level', FirewallLogLevel::class),
            $request->enum('outbound_log_level', FirewallLogLevel::class),
            $request->validated('digest'),
        );

        Audit::record(
            AuditEvent::SERVER_FIREWALL_OPTIONS_UPDATED,
            subject: $server,
            properties: [
                'inbound_policy' => $inboundPolicy?->value,
                'outbound_policy' => $outboundPolicy?->value,
            ],
        );

        return $options;
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
        $rule = $request->toRuleData();

        $this->firewallService->createRule(
            $server,
            $rule,
            $request->validated('position'),
        );

        Audit::record(
            AuditEvent::SERVER_FIREWALL_RULE_CREATED,
            subject: $server,
            properties: self::ruleProperties($rule),
        );

        return response()->noContent();
    }

    public function update(UpdateFirewallRuleRequest $request, Server $server, int $position)
    {
        $rule = $request->toRuleData();

        $this->firewallService->updateRule($server, $position, $rule, $rule->digest);

        Audit::record(
            AuditEvent::SERVER_FIREWALL_RULE_UPDATED,
            subject: $server,
            properties: ['position' => $position] + self::ruleProperties($rule),
        );

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

        Audit::record(
            AuditEvent::SERVER_FIREWALL_RULE_MOVED,
            subject: $server,
            properties: [
                'from' => $position,
                'to' => $request->validated('position'),
            ],
        );

        return response()->noContent();
    }

    public function destroy(DeleteFirewallRuleRequest $request, Server $server, int $position)
    {
        $this->firewallService->deleteRule($server, $position, $request->validated('digest'));

        // Only the position: rules live in Proxmox, not here, so there is no stored rule left to
        // describe once it is gone.
        Audit::record(
            AuditEvent::SERVER_FIREWALL_RULE_DELETED,
            subject: $server,
            properties: ['position' => $position],
        );

        return response()->noContent();
    }

    /**
     * The parts of a rule worth keeping in the log — enough to see what was opened or closed,
     * without copying the whole payload in.
     */
    private static function ruleProperties(FirewallRuleData $rule): array
    {
        return array_filter([
            'direction' => $rule->direction->value,
            'action' => $rule->action->value,
            'protocol' => $rule->protocol,
            'macro' => $rule->macro,
            'source_address' => $rule->sourceAddress,
            'destination_address' => $rule->destinationAddress,
            'source_port' => $rule->sourcePort,
            'destination_port' => $rule->destinationPort,
            'is_enabled' => $rule->isEnabled,
            'comment' => $rule->comment,
        ], fn ($value) => $value !== null && $value !== '');
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
