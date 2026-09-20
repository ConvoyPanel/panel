<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Server\MigrateServerAction;
use App\Data\Server\Deployments\DeploymentData;
use App\Data\Server\Migration\MigrationPlanData;
use App\Data\Server\Migration\MigrationPreviewData;
use App\Enums\Audit\AuditEvent;
use App\Exceptions\Service\Server\MigrationRefusedException;
use App\Facades\Audit;
use App\Http\Requests\Admin\Servers\MigrateServerRequest;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\ServerMigrationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Moving a server to another member of its cluster.
 *
 * Read before write, deliberately: `index` draws the picker, `show` resolves
 * one destination down to the addresses the operator will actually see, and
 * only then does `store` commit. The verdict is recomputed on every one of
 * them, so a dialog left open while a pool is detached cannot act on what it
 * was showing.
 */
class ServerMigrationController
{
    public function __construct(
        private ServerMigrationService $migration,
        private MigrateServerAction $action,
    ) {}

    /** Every member of the cluster, with the verdict for sending this server to it. */
    public function index(Server $server): MigrationPlanData
    {
        $this->refuseWhileBusy($server);

        return $this->migration->plan($server);
    }

    /** One destination, down to the addresses it keeps, loses and gains. */
    public function show(Server $server, int $destination): MigrationPreviewData
    {
        $this->refuseWhileBusy($server);

        return $this->migration->preview($server, Node::findOrFail($destination));
    }

    /**
     * @throws MigrationRefusedException
     */
    public function store(MigrateServerRequest $request, Server $server): DeploymentData
    {
        $this->refuseWhileBusy($server);

        $target = Node::findOrFail($request->validated('node_id'));

        $deployment = $this->action->execute(
            $server,
            $target,
            (bool) $request->validated('acknowledge_address_change', false),
        );

        Audit::record(
            AuditEvent::ADMIN_SERVER_MIGRATED,
            subject: $server,
            properties: [
                'from' => $server->node->name,
                'to' => $target->name,
                'vmid' => $server->vmid,
            ],
        );

        return DeploymentData::from($deployment->load('steps'))->include('steps');
    }

    /**
     * An install, a restore or another migration owns the guest's config while
     * it runs. Stacking a migration on top of one is how two chains end up
     * writing the same rows in an order neither of them chose.
     */
    private function refuseWhileBusy(Server $server): void
    {
        if ($server->lifecycle->isBusy()) {
            throw new ConflictHttpException(
                'This server is busy. Wait for the operation in progress to finish before migrating it.',
            );
        }
    }
}
