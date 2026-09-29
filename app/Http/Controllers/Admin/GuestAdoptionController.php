<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Server\AdoptGuestAction;
use App\Data\Server\Adoption\AdoptableGuestListData;
use App\Data\Server\Adoption\GuestAdoptionPreviewData;
use App\Data\Server\ServerData;
use App\Enums\Audit\AuditEvent;
use App\Exceptions\Service\Server\AdoptionRefusedException;
use App\Facades\Audit;
use App\Http\Requests\Admin\Servers\AdoptGuestRequest;
use App\Models\Node;
use App\Services\Servers\GuestAdoptionService;

/**
 * Guests that exist on a registered node and that Convoy does not own.
 *
 * `index` is panel-wide because the question is: it asks one reachable member
 * per cluster and merges the answers. `show` and `store` are node-scoped,
 * because by then the operator has picked a specific guest.
 */
class GuestAdoptionController
{
    public function __construct(
        private GuestAdoptionService $adoption,
        private AdoptGuestAction $action,
    ) {}

    public function index(): AdoptableGuestListData
    {
        return $this->adoption->adoptable();
    }

    /** What adopting this guest would do. Two GETs to PVE and no writes. */
    public function show(Node $node, int $vmid): GuestAdoptionPreviewData
    {
        return $this->adoption->preview($node, $vmid);
    }

    /**
     * @throws AdoptionRefusedException
     */
    public function store(AdoptGuestRequest $request, Node $node, int $vmid): ServerData
    {
        $server = $this->action->execute($node, $vmid, [
            'user_id' => (int) $request->validated('user_id'),
            'name' => $request->validated('name'),
            'hostname' => $request->validated('hostname'),
        ]);

        Audit::record(
            AuditEvent::ADMIN_SERVER_ADOPTED,
            subject: $server,
            properties: [
                'node' => $node->name,
                'vmid' => $vmid,
                'addresses_claimed' => $server->addresses()->count(),
                'unclaimed' => $server->flag_reason,
            ],
        );

        return ServerData::from($server->load('node'))->include('node');
    }
}
