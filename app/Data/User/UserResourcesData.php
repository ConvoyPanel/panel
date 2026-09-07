<?php

namespace App\Data\User;

use App\Casts\StorageSizeCast;
use App\Enums\Server\ServerLifecycle;
use App\Models\Server;
use App\Models\User;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What one account is holding across the fleet, as a single aggregate query.
 *
 * Deliberately not derived by loading the user's servers and summing in PHP: an
 * account with a few hundred servers would hydrate a few hundred models — plus
 * their casts — to produce six integers.
 */
#[MapInputName(SnakeCaseMapper::class)]
class UserResourcesData extends Data
{
    /**
     * `memory`, `disk`, `bandwidth_usage` and `bandwidth_limit` are stored in mebibytes and read
     * back as bytes by {@see StorageSizeCast}. Aggregating in SQL bypasses the cast, so
     * the conversion happens here instead — and every sum has to skip the `-1` sentinel rows, which
     * would otherwise quietly subtract a mebibyte each.
     */
    private const BYTES_PER_MIB = 1048576;

    public function __construct(
        public int $serversCount,
        public int $suspendedCount,
        /** Servers that are not yet built, or whose install failed — see {@see ServerLifecycle}. */
        public int $unbuiltCount,
        public int $nodesCount,
        public int $cpu,
        /** Bytes. */
        public int $memory,
        /** Bytes. */
        public int $disk,
        /** Bytes used against the current billing period. */
        public int $bandwidthUsage,
        /**
         * Bytes, or null when the total is unbounded because at least one server is unmetered.
         *
         * Not a quota the account is held to — nothing enforces a per-user ceiling. It is the sum
         * of the per-server limits, which is the only figure the usage above can be read against.
         */
        public ?int $bandwidthLimit,
    ) {}

    public static function forUser(User $user): self
    {
        /*
         * CASE rather than the FILTER clause both Postgres and modern SQLite support: this is the
         * one query in the panel that would care, and the portable spelling costs nothing.
         */
        $totals = Server::query()
            ->where('user_id', '=', $user->id)
            ->selectRaw('COUNT(*) AS servers_count')
            ->selectRaw('COUNT(DISTINCT node_id) AS nodes_count')
            ->selectRaw('SUM(CASE WHEN suspended_at IS NOT NULL THEN 1 ELSE 0 END) AS suspended_count')
            ->selectRaw(
                'SUM(CASE WHEN lifecycle <> ? THEN 1 ELSE 0 END) AS unbuilt_count',
                [ServerLifecycle::READY->value],
            )
            ->selectRaw('SUM(CASE WHEN cpu >= 0 THEN cpu ELSE 0 END) AS cpu_total')
            ->selectRaw('SUM(CASE WHEN memory >= 0 THEN memory ELSE 0 END) AS memory_total')
            ->selectRaw('SUM(CASE WHEN disk >= 0 THEN disk ELSE 0 END) AS disk_total')
            ->selectRaw('SUM(CASE WHEN bandwidth_usage >= 0 THEN bandwidth_usage ELSE 0 END) AS bandwidth_usage_total')
            ->selectRaw('SUM(CASE WHEN bandwidth_limit >= 0 THEN bandwidth_limit ELSE 0 END) AS bandwidth_limit_total')
            ->selectRaw('SUM(CASE WHEN bandwidth_limit < 0 THEN 1 ELSE 0 END) AS unmetered_count')
            ->first();

        $unmetered = (int) ($totals?->unmetered_count ?? 0);

        return new self(
            serversCount: (int) ($totals?->servers_count ?? 0),
            suspendedCount: (int) ($totals?->suspended_count ?? 0),
            unbuiltCount: (int) ($totals?->unbuilt_count ?? 0),
            nodesCount: (int) ($totals?->nodes_count ?? 0),
            cpu: (int) ($totals?->cpu_total ?? 0),
            memory: (int) ($totals?->memory_total ?? 0) * self::BYTES_PER_MIB,
            disk: (int) ($totals?->disk_total ?? 0) * self::BYTES_PER_MIB,
            bandwidthUsage: (int) ($totals?->bandwidth_usage_total ?? 0) * self::BYTES_PER_MIB,
            bandwidthLimit: $unmetered > 0
                ? null
                : (int) ($totals?->bandwidth_limit_total ?? 0) * self::BYTES_PER_MIB,
        );
    }
}
