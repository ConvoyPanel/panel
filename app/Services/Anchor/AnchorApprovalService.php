<?php

namespace App\Services\Anchor;

use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Promotes a machine that introduced itself into the thing it becomes.
 *
 * The uuid and secret travel with it. The agent wrote them to disk at
 * enrollment and has been heartbeating with them ever since; minting new ones
 * here would silently invalidate a config nobody asked it to rewrite, and the
 * machine would drop off moments after being let in.
 *
 * The enrollment row is then gone. It described a claim, and the claim has been
 * settled -- keeping it would leave two rows for one machine, which is the
 * thing this whole change exists to stop.
 */
class AnchorApprovalService
{
    /**
     * @param  array<string, mixed>  $attributes  the operator's decisions: location,
     *                                            address, credentials, capacity
     */
    public function approveNode(AnchorEnrollment $enrollment, array $attributes): Node
    {
        return DB::transaction(function () use ($enrollment, $attributes) {
            $node = Node::create([
                // Defaults for what the approval screen does not ask about.
                // TLS verification errs toward on: turning it off is a decision
                // about a trusted private path, never an omission.
                'verify_tls' => true,
                ...$attributes,
                'agent_uuid' => $enrollment->uuid,
                'agent_secret' => $enrollment->secret,
                'agent_enrollment_key_id' => $enrollment->enrollment_key_id,
                'agent_reported_facts' => $enrollment->reported_facts,
                'agent_enrolled_at' => $enrollment->enrolled_at,
                'agent_last_seen_at' => $enrollment->last_seen_at,
                'agent_version' => $enrollment->version,
                'agent_protocol_min' => $enrollment->protocol_min,
                'agent_protocol_max' => $enrollment->protocol_max,
                'agent_capabilities' => $enrollment->capabilities,
            ]);

            $enrollment->delete();

            return $node;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function approveRelay(AnchorEnrollment $enrollment, array $attributes): Relay
    {
        return DB::transaction(function () use ($enrollment, $attributes) {
            $relay = Relay::create([
                ...Arr::only($attributes, ['name', 'public_url', 'panel_url_override']),
                'uuid' => $enrollment->uuid,
                'secret' => $enrollment->secret,
                'enrollment_key_id' => $enrollment->enrollment_key_id,
                'reported_facts' => $enrollment->reported_facts,
                'enrolled_at' => $enrollment->enrolled_at,
                'last_seen_at' => $enrollment->last_seen_at,
                'version' => $enrollment->version,
                'protocol_min' => $enrollment->protocol_min,
                'protocol_max' => $enrollment->protocol_max,
                'capabilities' => $enrollment->capabilities,
            ]);

            $enrollment->delete();

            return $relay;
        });
    }

    /**
     * The node fields the machine already answered, for pre-filling the
     * approval screen.
     *
     * Capacity comes back as facts the host reported about itself, not as
     * policy: `memory_overallocate` is absent on purpose, because how far to
     * oversubscribe is a decision and the host has no view on it.
     *
     * @return array<string, mixed>
     */
    public function suggestions(AnchorEnrollment $enrollment): array
    {
        $facts = $enrollment->reported_facts ?? [];
        $cpu = Arr::get($facts, 'cpu', []);

        return array_filter([
            'display_name' => Arr::get($facts, 'hostname') ?: $enrollment->name,
            'name' => Arr::get($facts, 'pve_node_name'),
            // The hostname the machine gave, else the address the request
            // actually came from -- the one reachability claim it cannot
            // overstate. Both are candidates for a human to confirm.
            'fqdn' => Arr::get($facts, 'hostname') ?: Arr::get($facts, 'observed_source_ip'),
            'socket_count' => Arr::get($cpu, 'sockets'),
            'core_count' => Arr::get($cpu, 'cores'),
            'cpu_count' => Arr::get($cpu, 'threads'),
            'memory' => Arr::get($facts, 'memory_bytes'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
