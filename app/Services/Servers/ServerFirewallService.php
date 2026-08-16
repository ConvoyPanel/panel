<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Firewall\FirewallLogEntryData;
use App\Data\Server\Proxmox\Firewall\FirewallMacroData;
use App\Data\Server\Proxmox\Firewall\FirewallOptionsData;
use App\Data\Server\Proxmox\Firewall\FirewallRefData;
use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Data\Server\Proxmox\Network\IpsetData;
use App\Data\Server\Proxmox\Network\LockedIpData;
use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\FirewallPolicy;
use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxFirewallClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServerFirewallService
{
    public function __construct(
        private ProxmoxFirewallClient $firewallClient,
    ) {}

    /**
     * Automatically configures the firewall options for IP address management.
     *
     * Deliberately limited to the two options the platform guarantees: the
     * firewall being on at all, and ipfilter, which is what makes the
     * per-interface anti-spoof ipsets below actually bind. The default
     * policies are NOT set here -- they belong to the user, who can change
     * them from the server's firewall page, and this method runs on every
     * network sync (address changes, rebuilds, VM syncs). Writing them here
     * would silently revert a user's default-deny posture the next time any
     * of those happened.
     *
     * @throws RequestException
     */
    public function configureFirewall(Server $server): void
    {
        $this->firewallClient->setServer($server)->updateOptions([
            'enable' => true,
            'ipfilter' => true,
        ]);
    }

    /**
     * @return Collection<int, FirewallRuleData>
     *
     * @throws RequestException
     */
    public function getRules(Server $server): Collection
    {
        return $this->firewallClient->setServer($server)->getRules();
    }

    /**
     * @throws RequestException
     */
    public function createRule(Server $server, FirewallRuleData $rule, ?int $position = null): void
    {
        $this->firewallClient->setServer($server);

        $desiredPosition = $position ?? $this->getRules($server)->count();

        /*
         * Proxmox ignores `pos` on create and always inserts at the top of the
         * ruleset -- verified against the live API, where a create sent with
         * `pos: 1` still landed at index 0 and pushed the existing rule down.
         *
         * That default is actively dangerous here: the first matching rule
         * wins, so a newly added "allow" would silently override every deny
         * rule already in the list. The rule is therefore moved into the
         * position the caller asked for straight after it is created.
         */
        $this->firewallClient->createRule($rule->toPayload());

        if ($desiredPosition > 0) {
            $this->firewallClient->moveRule(0, $this->toInsertIndex(0, $desiredPosition));
        }
    }

    /**
     * Translates "the index this rule should end up at" into the index Proxmox
     * actually wants.
     *
     * `moveto` names the slot to insert *before* in the list as it stands
     * right now, so a rule travelling downwards has to account for its own
     * removal shifting everything above it up by one. Verified live: moving
     * index 0 to `moveto: 2` in a three-rule set leaves the rule at index 1.
     */
    private function toInsertIndex(int $from, int $to): int
    {
        return $to > $from ? $to + 1 : $to;
    }

    /**
     * Replaces the rule at $position.
     *
     * Fields the caller dropped are named in the request's `delete` list --
     * omitting them would leave the old value in place, and sending them empty
     * does not clear them either.
     *
     * @throws RequestException
     */
    public function updateRule(
        Server $server,
        int $position,
        FirewallRuleData $rule,
        ?string $digest = null,
    ): void {
        $this->firewallClient->setServer($server);

        $existing = $this->findRule($server, $position);
        $payload = $rule->toPayload();
        $cleared = $rule->clearedKeysAgainst($existing);

        if ($cleared !== []) {
            $payload['delete'] = implode(',', $cleared);
        }

        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        $this->guardAgainstStaleWrite(
            fn () => $this->firewallClient->updateRule($position, $payload),
            $digest,
        );
    }

    /**
     * @throws RequestException
     */
    public function moveRule(
        Server $server,
        int $position,
        int $newPosition,
        ?string $digest = null,
    ): void {
        // Proves the rule exists before asking Proxmox to move it, so a stale
        // index fails as a 404 rather than silently reordering something else.
        $this->findRule($server, $position);

        $this->firewallClient->setServer($server);

        $this->guardAgainstStaleWrite(
            fn () => $this->firewallClient->moveRule(
                $position,
                $this->toInsertIndex($position, $newPosition),
                $digest,
            ),
            $digest,
        );
    }

    /**
     * @throws RequestException
     */
    public function deleteRule(Server $server, int $position, ?string $digest = null): void
    {
        $this->findRule($server, $position);

        $this->firewallClient->setServer($server);

        $this->guardAgainstStaleWrite(
            fn () => $this->firewallClient->deleteRule($position, $digest),
            $digest,
        );
    }

    /**
     * @throws RequestException
     */
    public function getOptions(Server $server): FirewallOptionsData
    {
        return $this->firewallClient->setServer($server)->getOptions();
    }

    /**
     * Applies the options a user is allowed to change.
     *
     * `enable` and `ipfilter` are deliberately absent: {@see configureFirewall}
     * rewrites both on every network sync, so accepting them here would offer
     * a control that silently reverts.
     *
     * @throws RequestException
     */
    public function updateOptions(
        Server $server,
        FirewallPolicy $inboundPolicy,
        FirewallPolicy $outboundPolicy,
        FirewallLogLevel $inboundLogLevel,
        FirewallLogLevel $outboundLogLevel,
        ?string $digest = null,
    ): FirewallOptionsData {
        $payload = [
            'policy_in' => $inboundPolicy->value,
            'policy_out' => $outboundPolicy->value,
            'log_level_in' => $inboundLogLevel->value,
            'log_level_out' => $outboundLogLevel->value,
        ];

        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        $this->firewallClient->setServer($server);

        $this->guardAgainstStaleWrite(
            fn () => $this->firewallClient->updateOptions($payload),
            $digest,
        );

        return $this->getOptions($server);
    }

    /**
     * Runs a write, translating Proxmox's digest refusal into a 409 the client
     * can act on. Only meaningful when a digest was actually sent.
     *
     * @throws ConfigModifiedException
     * @throws RequestException
     */
    private function guardAgainstStaleWrite(callable $write, ?string $digest): void
    {
        try {
            $write();
        } catch (RequestException $e) {
            if ($digest !== null && $this->isConfigModifiedError($e)) {
                throw new ConfigModifiedException;
            }

            throw $e;
        }
    }

    /**
     * Whether Proxmox rejected the write because the digest no longer matched.
     * Same wording as {@see \App\Services\Proxmox\Server\ProxmoxConfigClient}.
     */
    private function isConfigModifiedError(RequestException $e): bool
    {
        return Str::contains(
            Str::lower($e->getMessage()),
            ['changed by other user', 'modified configuration'],
        );
    }

    /**
     * @return Collection<int, FirewallRefData>
     *
     * @throws RequestException
     */
    public function getRefs(Server $server): Collection
    {
        return $this->firewallClient->setServer($server)->getRefs();
    }

    /**
     * @return Collection<int, FirewallLogEntryData>
     *
     * @throws RequestException
     */
    public function getLog(Server $server, int $start = 0, int $limit = 100): Collection
    {
        return $this->firewallClient
            ->setServer($server)
            ->getLog($start, $limit)
            // Proxmox returns a single `{"n":1,"t":"no content"}` line rather
            // than an empty list when there is nothing to show. Passing that
            // through would render the literal words as a log entry.
            ->reject(fn (FirewallLogEntryData $entry) => trim($entry->raw) === ''
                || strtolower(trim($entry->raw)) === 'no content')
            ->values();
    }

    /**
     * The cluster's macro list, cached because it only changes with a Proxmox
     * upgrade and every rule form asks for it.
     *
     * @return Collection<int, FirewallMacroData>
     *
     * @throws RequestException
     */
    public function getMacros(Server $server): Collection
    {
        return Cache::remember(
            "nodes.{$server->node_id}.firewall.macros",
            now()->addHour(),
            fn () => $this->firewallClient->setServer($server)->getMacros(),
        );
    }

    /**
     * @throws RequestException
     */
    private function findRule(Server $server, int $position): FirewallRuleData
    {
        $rule = $this->getRules($server)->firstWhere('position', $position);

        if (! $rule instanceof FirewallRuleData) {
            throw new NotFoundHttpException('Firewall rule not found');
        }

        return $rule;
    }

    /**
     * Deletes an IP set and unlocks all IP addresses associated with it.
     *
     * @throws RequestException
     */
    public function deleteIpset(Server $server, string|IpsetData $ipset): void
    {
        $this->firewallClient->setServer($server);

        $this
            ->firewallClient
            ->getLockedIps($ipset)
            ->each(function (LockedIpData $lockedIp) use ($ipset) {
                $this->firewallClient->unlockIp($ipset, $lockedIp);
            });

        $this->firewallClient->deleteIpset($ipset);
    }

    /**
     * Clears all IP sets and unlocks all IP addresses associated with them.
     *
     * @throws RequestException
     */
    public function clearIpsets(Server $server): void
    {
        $this->firewallClient->setServer($server);

        $this
            ->firewallClient
            ->getIpsets()
            ->each(function (IpsetData $ipset) use ($server) {
                $this->deleteIpset($server, $ipset);
            });
    }

    /**
     * Locks the specified IP addresses in the given IP set.
     *
     * @throws RequestException
     */
    public function lockIps(Server $server, array $addresses, string|IpsetData $ipset): void
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        $this->firewallClient->setServer($server);

        $this->firewallClient->createIpset($ipset);

        foreach ($addresses as $address) {
            $this->firewallClient->lockIp($ipset, $address);
        }
    }
}
