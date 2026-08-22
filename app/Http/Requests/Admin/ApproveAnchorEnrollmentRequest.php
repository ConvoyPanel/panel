<?php

namespace App\Http\Requests\Admin;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\BaseApiRequest;
use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Rules\Hostname;

/**
 * What an operator still has to answer when letting a machine in.
 *
 * Two kinds of field, and the distinction is the point of the whole feature:
 * things the host cannot know (how this panel routes back to it) and things it
 * must not decide (which location it belongs to, how far to oversubscribe it).
 * Everything else is carried over from the report and only shown for
 * confirmation.
 */
class ApproveAnchorEnrollmentRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $enrollment = $this->parameter('anchor_enrollment', AnchorEnrollment::class);

        if ($enrollment->mode === AnchorMode::RELAY) {
            return [
                'name' => ['sometimes', 'string', 'max:191'],
                'public_url' => ['required', 'url:http,https', 'max:2048'],
                'panel_url_override' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            ];
        }

        // Array form throughout: `$validationRules` mixes pipe strings with
        // arrays, and appending a rule object to a pipe string silently makes
        // the whole string one rule name.
        $node = Node::getRules();

        return [
            // Operator policy. Nothing the host reports may supply these.
            'location_id' => $node['location_id'],
            'memory_overallocate' => $node['memory_overallocate'],
            'relay_id' => ['sometimes', 'nullable', 'integer', 'exists:relays,id'],

            // How the panel reaches the host, and how it reaches the agent.
            'fqdn' => [...$node['fqdn'], new Hostname],
            'port' => $node['port'],
            'verify_tls' => ['sometimes', 'boolean'],
            'agent_public_url' => ['required', 'url:http,https', 'max:2048'],
            'agent_panel_url_override' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],

            /*
             * Still typed, and the last thing standing between this screen and
             * one field. The agent runs as root on the host and can mint its own
             * API token; until it does, the operator pastes one.
             */
            'token_id' => $node['token_id'],
            'token_secret' => $node['token_secret'],

            // Reported by the host and pre-filled; editable because a report is
            // evidence, not authority.
            'display_name' => $node['display_name'],
            'name' => $node['name'],
            'socket_count' => $node['socket_count'],
            'core_count' => $node['core_count'],
            'cpu_count' => $node['cpu_count'],
            'memory' => $node['memory'],
        ];
    }
}
