<?php

namespace Database\Seeders;

use Convoy\Enums\Api\ApiKeyType;
use Convoy\Models\ActivityLog;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\Coterm;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\SSHKey;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Prod-shaped v4 data for the upgrade rehearsal (database/upgrade/rehearse.sh).
 * It runs inside a v4 checkout, against v4's own models, so morph class names,
 * MiB-stored sizes and enum values are exactly what a real v4 install holds.
 *
 * The tail seeds the shapes that broke a rehearsal once: a node without a
 * network, pools attached to nothing, and servers in rare states.
 *
 * Copied from the new panel's database/cutover/fixtures, which seeds the same
 * v4 data for its own cutover rehearsal.
 */
class V4RehearsalSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42);
        fake()->seed(42);

        $admin = User::factory()->create([
            'name' => 'Root Admin', 'email' => 'admin@example.com', 'root_admin' => true,
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['aaaa-bbbb', 'cccc-dddd'])),
        ]);
        $users = User::factory()->count(30)->create();
        $users->push(User::factory()->create(['name' => 'Zoë Ångström 李', 'email' => 'zoe@example.com']));
        $users->push($admin);

        $coterms = collect([
            Coterm::create(['name' => 'coterm-a', 'is_tls_enabled' => true, 'fqdn' => 'coterm-a.example.com', 'port' => 443, 'token_id' => Str::random(16), 'token' => Str::random(64)]),
            Coterm::create(['name' => 'coterm-b', 'is_tls_enabled' => false, 'fqdn' => 'coterm-b.example.com', 'port' => 2115, 'token_id' => Str::random(16), 'token' => Str::random(64)]),
        ]);

        $nodes = collect();
        foreach (['us-east', 'eu-west', 'ap-south'] as $li => $code) {
            $loc = Location::create(['short_code' => $code, 'description' => "Location $code"]);
            foreach (range(1, 2) as $n) {
                $nodes->push(Node::factory()->for($loc)->create([
                    'name' => "pve-$code-$n",
                    'cluster' => "pve$n",
                    'fqdn' => "pve-$code-$n.example.com",
                    'verify_tls' => $n % 2 === 0,
                    'token_id' => 'root@pam!convoy',
                    'secret' => Str::uuid()->toString(),
                    'memory' => 256 * 1024 ** 3,      // 256 GiB (bytes; cast stores MiB)
                    'disk' => 8 * 1024 ** 4,          // 8 TiB
                    'memory_overallocate' => 10,
                    'disk_overallocate' => -1,
                    'vm_storage' => 'local-lvm',
                    'backup_storage' => 'pbs',
                    'iso_storage' => 'local',
                    'network' => 'vmbr0',
                    'coterm_id' => $li < 2 ? $coterms[$li]->id : null,
                ]));
            }
        }

        // Template groups + templates per node.
        foreach ($nodes as $node) {
            foreach (['Linux', 'Windows'] as $gi => $g) {
                $group = TemplateGroup::create(['node_id' => $node->id, 'name' => $g, 'hidden' => false]);
                foreach (['Ubuntu 24.04', 'Debian 12', 'Rocky 9'] as $ti => $t) {
                    Template::create(['template_group_id' => $group->id, 'name' => $gi ? "Windows Server 20{$ti}2" : $t, 'vmid' => 9000 + $gi * 10 + $ti, 'hidden' => $ti === 2]);
                }
            }
        }

        // Address pools: a v4 /24 per location + a v6 pool, attached to nodes.
        $pools = collect();
        foreach ($nodes->groupBy('location_id')->values() as $i => $locNodes) {
            $v4 = AddressPool::create(['name' => 'v4 block '.($i + 1)]);
            $v6 = AddressPool::create(['name' => 'v6 block '.($i + 1)]);
            $v4->nodes()->attach($locNodes->pluck('id'));
            $v6->nodes()->attach($locNodes->pluck('id'));
            foreach (range(10, 60) as $h) {
                Address::create(['address_pool_id' => $v4->id, 'type' => 'ipv4', 'address' => "198.51.10$i.$h", 'cidr' => 24, 'gateway' => "198.51.10$i.1", 'mac_address' => $h % 3 ? null : sprintf('bc:24:11:%02x:%02x:%02x', $i, $h, 7)]);
            }
            foreach (range(1, 20) as $h) {
                Address::create(['address_pool_id' => $v6->id, 'type' => 'ipv6', 'address' => sprintf('2001:db8:%x::%x', $i, $h), 'cidr' => 64, 'gateway' => sprintf('2001:db8:%x::1', $i), 'mac_address' => null]);
            }
            $pools->push($v4, $v6);
        }

        // Servers, with addresses, backups, ssh keys.
        $statuses = [null, null, null, null, 'installing', 'install_failed', 'suspended', 'restoring_backup'];
        foreach (range(1, 80) as $s) {
            $node = $nodes[$s % $nodes->count()];
            $server = Server::factory()->create([
                'user_id' => $users[$s % $users->count()]->id,
                'node_id' => $node->id,
                'vmid' => 100 + $s,
                'status' => $statuses[$s % count($statuses)],
                'memory' => (2 ** ($s % 5)) * 1024 ** 3,
                'disk' => (20 + $s) * 1024 ** 3,
                'bandwidth_limit' => $s % 4 ? 1024 ** 4 : null,
                'bandwidth_usage' => $s * 1024 ** 2,
                'snapshot_limit' => $s % 3 ? 2 : null,
                'backup_limit' => $s % 2 ? 5 : null,
                'hydrated_at' => $s % 5 ? now()->subMinutes($s) : null,
                'description' => $s % 7 ? null : "Server #$s — émoji 🚀",
            ]);
            $free = Address::whereNull('server_id')
                ->whereIn('address_pool_id', DB::table('address_pool_to_node')->where('node_id', $node->id)->pluck('address_pool_id'))
                ->orderBy('id')->get()->groupBy('type');
            foreach (['ipv4', 'ipv6'] as $type) {
                if ($s % 6 === 0 && $type === 'ipv6') {
                    continue;
                }
                optional($free->get($type)?->first())->update(['server_id' => $server->id]);
            }
            foreach (range(1, $s % 4) as $b) {
                Backup::factory()->create(['server_id' => $server->id, 'is_successful' => $b !== 3, 'completed_at' => $b === 3 ? null : now()->subDays($b), 'size' => $b * 3 * 1024 ** 3, 'deleted_at' => $b === 2 && $s % 5 === 0 ? now() : null]);
            }
        }

        foreach ($users->take(10) as $u) {
            SSHKey::create(['user_id' => $u->id, 'name' => 'laptop', 'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI'.Str::random(43).' '.$u->email]);
        }

        foreach ($nodes as $node) {
            foreach (range(1, 3) as $i) {
                ISO::factory()->create(['node_id' => $node->id, 'name' => "debian-12.$i", 'file_name' => "debian-12.$i-amd64-netinst.iso", 'size' => 650 * 1024 ** 2, 'hidden' => $i === 3]);
            }
        }

        // API tokens: application keys + account keys.
        $admin->createToken('WHMCS', ApiKeyType::APPLICATION);
        $admin->createToken('Blesta', ApiKeyType::APPLICATION, ['*']);
        foreach ($users->take(5) as $u) {
            $u->createToken('cli', ApiKeyType::ACCOUNT);
        }

        // Activity logs with morph actor + subjects, the way v4 writes them.
        foreach (Server::limit(25)->get() as $i => $server) {
            $log = ActivityLog::forceCreate([
                'batch' => $i % 3 ? null : (string) Str::uuid(),
                'event' => ['server:power.start', 'server:backup.create', 'server:settings.rename'][$i % 3],
                'ip' => $i % 2 ? '203.0.113.'.$i : '2001:db8::'.$i,
                'actor_type' => $admin->getMorphClass(),
                'actor_id' => $i % 4 ? $admin->id : $server->user_id,
                'properties' => ['old' => 'a', 'new' => "b-$i", 'nested' => ['flag' => (bool) ($i % 2)]],
                'timestamp' => now()->subHours($i),
            ]);
            DB::table('activity_log_subjects')->insert(['activity_log_id' => $log->id, 'subject_type' => $server->getMorphClass(), 'subject_id' => $server->id]);
        }

        // Edge cases.
        $bare = Node::factory()->for(Location::first())->create([
            'name' => 'pve-nonet', 'cluster' => 'pve9', 'fqdn' => 'pve-nonet.example.com',
            'secret' => Str::uuid()->toString(), 'network' => 'placeholder',
        ]);
        DB::table('nodes')->where('id', $bare->id)->update(['network' => null]);
        AddressPool::create(['name' => 'orphan pool'])->addresses()->create(['type' => 'ipv4', 'address' => '192.0.2.10', 'cidr' => 24, 'gateway' => '192.0.2.1']);
        $nonet = AddressPool::create(['name' => 'nonet pool']);
        $nonet->nodes()->attach($bare->id);
        $nonet->addresses()->create(['type' => 'ipv4', 'address' => '192.0.3.10', 'cidr' => 24, 'gateway' => '192.0.3.1']);
        DB::table('servers')->where('id', 3)->update(['status' => 'restoring_snapshot']);
        DB::table('servers')->where('id', 4)->update(['status' => 'deleting']);

        DB::table('password_reset_tokens')->insert(['email' => 'zoe@example.com', 'token' => Str::random(60), 'created_at' => now()]);
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}', 'exception' => 'Boom', 'failed_at' => now()]);
    }
}
