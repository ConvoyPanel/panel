<?php

namespace Database\Seeders;

use Convoy\Enums\Server\Status;
use Convoy\Models\Backup;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Servers\ServerCreationService;
use Illuminate\Database\Seeder;

/**
 * Fixtures for the admin overview's attention card.
 *
 * The card renders a group two different ways, so the point of this seeder is to
 * put both on screen at once:
 *
 *   failed servers   1 record   -- named outright, links straight at itself
 *   failed backups   28 records -- several, so the row opens the sheet, and past
 *                                  the endpoint's 25-record cap so the footer
 *                                  admits what it is not showing
 *
 * To see the multi-record row for servers (and the "Deletion failed" wording,
 * which one install failure never produces):
 *
 *   ddev artisan tinker --execute="Convoy\Models\Server::where('name', 'syd-web-01')
 *       ->update(['status' => 'deletion_failed']);"
 *
 * Healthy noise (successful and pending backups, servers mid-install, mid-delete
 * and suspended) comes along so the surrounding cards are not all zeros. Those
 * last two are also negative checks: mid-delete and suspended both belong in
 * Server State and NOT on the attention card.
 *
 * Everything lands on a placeholder node, never the live one from
 * DevNodeSeeder: a card link opens the server's admin page, and a phantom vmid
 * on a real host is a worse failure than one on a node that was never real.
 *
 * Idempotent -- it drops its own servers (by name) and their backups first, so
 * re-running does not multiply the fixtures.
 *
 * Run:  ddev artisan db:seed --class=AttentionSeeder
 */
class AttentionSeeder extends Seeder
{
    private const NODE_NAME = 'ord-hv-01';

    private const NODE_FQDN = 'ord-hv-01.fixtures.invalid';

    /** Fixture server names, also the handle used to clean up a previous run. */
    private const FAILED = ['ord-web-04'];

    private const SUSPENDED = ['fra-mail-01'];

    private const BACKUP_OWNERS = ['ams-app-01', 'ams-app-02', 'ams-cache-01'];

    private const HEALTHY = ['syd-web-01', 'syd-web-02', 'syd-queue-01', 'syd-api-03'];

    public function run(ServerCreationService $service): void
    {
        $names = array_merge(self::FAILED, self::SUSPENDED, self::BACKUP_OWNERS, self::HEALTHY);

        $stale = Server::query()->whereIn('name', $names)->pluck('id');
        Backup::query()->whereIn('server_id', $stale)->forceDelete();
        Server::query()->whereIn('id', $stale)->delete();

        $user = User::query()->orderBy('id')->first() ?? User::factory()->create();
        $node = $this->placeholderNode();

        $make = function (string $name, ?string $status) use ($service, $user, $node): Server {
            $uuid = $service->generateUniqueUuidCombo();

            return Server::factory()->create([
                'uuid' => $uuid,
                'uuid_short' => substr($uuid, 0, 8),
                'name' => $name,
                'hostname' => $name.'.example.com',
                'status' => $status,
                'user_id' => $user->id,
                'node_id' => $node->id,
                'cpu' => 2,
                'memory' => 2048 * 1024 * 1024,
                'disk' => 20 * 1024 * 1024 * 1024,
                'backup_limit' => 16,
                'snapshot_limit' => 16,
                'bandwidth_limit' => 100 * 1024 * 1024 * 1024,
            ]);
        };

        // Exactly one, to hold the card's single-record path open.
        $make(self::FAILED[0], Status::INSTALL_FAILED->value);

        // Not failures: suspended, mid-install and mid-delete are Server State's
        // business and must stay off the card.
        $make(self::SUSPENDED[0], Status::SUSPENDED->value);
        $make(self::HEALTHY[0], Status::INSTALLING->value);
        $make(self::HEALTHY[1], Status::INSTALLING->value);
        $make(self::HEALTHY[2], Status::DELETING->value);
        $make(self::HEALTHY[3], Status::RESTORING_BACKUP->value);

        $owners = collect(self::BACKUP_OWNERS)->map(fn (string $name) => $make($name, null));

        // 28 failures against a cap of 25. A backup counts as failed only once it
        // has given up -- completed_at set, is_successful false -- so a pending
        // backup (completed_at null) is neither failed nor successful.
        foreach (range(1, 28) as $i) {
            $this->backup($owners[$i % $owners->count()], "daily-{$i}", false, now()->subHours($i));
        }

        foreach (range(1, 6) as $i) {
            $this->backup($owners[$i % $owners->count()], "weekly-{$i}", true, now()->subDays($i));
        }

        foreach (range(1, 3) as $i) {
            $this->backup($owners[$i % $owners->count()], "in-flight-{$i}", false, null);
        }

        $this->command->info(sprintf(
            'AttentionSeeder: %d servers and %d backups on node #%d (%s).',
            count($names),
            37,
            $node->id,
            $node->name,
        ));
    }

    private function backup(Server $server, string $name, bool $successful, ?object $completedAt): void
    {
        Backup::factory()->create([
            'server_id' => $server->id,
            'name' => $name,
            'is_successful' => $successful,
            'is_locked' => false,
            'completed_at' => $completedAt,
        ]);
    }

    /**
     * The node the fixtures hang off. Its own, rather than whichever node happens
     * to be first: the card prints the node name in every detail line ("Deletion
     * failed on ..."), so a faker word there makes the thing being tested harder
     * to read. `.invalid` is reserved by RFC 2606 and can never resolve, which is
     * the point -- nothing here should ever reach a host.
     */
    private function placeholderNode(): Node
    {
        $existing = Node::query()->where('fqdn', self::NODE_FQDN)->first();

        if ($existing) {
            return $existing;
        }

        return Node::factory()
            ->for(Location::query()->firstOrCreate(
                ['short_code' => 'ord'],
                ['description' => 'Chicago (fixtures)'],
            ))
            ->create([
                'name' => self::NODE_NAME,
                'cluster' => self::NODE_NAME,
                'fqdn' => self::NODE_FQDN,
            ]);
    }
}
