<?php

namespace Convoy\Transformers\Admin;

use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

class OverviewTransformer extends TransformerAbstract
{
    public function transform(array $overview): array
    {
        return [
            'generated_at' => $overview['generated_at'],
            'summary' => [
                'servers' => $overview['summary']['servers'],
                'nodes' => $overview['summary']['nodes'],
                'users' => $overview['summary']['users'],
                'locations' => $overview['summary']['locations'],
                'failed_servers' => $overview['summary']['failed_servers'],
            ],
            'servers' => [
                'total' => $overview['servers']['total'],
                'ready' => $overview['servers']['ready'],
                'installing' => $overview['servers']['installing'],
                'suspended' => $overview['servers']['suspended'],
                'restoring' => $overview['servers']['restoring'],
                'deleting' => $overview['servers']['deleting'],
                'failed' => $overview['servers']['failed'],
                'statuses' => $this->arrayFrom($overview['servers']['statuses']),
            ],
            'capacity' => [
                'memory' => $this->metric($overview['capacity']['memory']),
                'disk' => $this->metric($overview['capacity']['disk']),
            ],
            'addresses' => [
                'pools' => $overview['addresses']['pools'],
                'total' => $overview['addresses']['total'],
                'assigned' => $overview['addresses']['assigned'],
                'available' => $overview['addresses']['available'],
                'percent' => $overview['addresses']['percent'],
            ],
            'backups' => [
                'total' => $overview['backups']['total'],
                'successful' => $overview['backups']['successful'],
                'pending' => $overview['backups']['pending'],
                'failed' => $overview['backups']['failed'],
            ],
            'isos' => [
                'total' => $overview['isos']['total'],
                'successful' => $overview['isos']['successful'],
                'pending' => $overview['isos']['pending'],
            ],
            'nodes' => collect($overview['nodes'])
                ->map(fn (array $node) => [
                    'id' => $node['id'],
                    'name' => $node['name'],
                    'cluster' => $node['cluster'],
                    'fqdn' => $node['fqdn'],
                    'servers' => $node['servers'],
                    'memory' => $this->metric($node['memory']),
                    'disk' => $this->metric($node['disk']),
                ])
                ->all(),
            'activity' => collect($overview['activity'])
                ->map(fn (array $activity) => [
                    'id' => $activity['id'],
                    'event' => $activity['event'],
                    'description' => $activity['description'],
                    'actor' => $activity['actor'],
                    'timestamp' => $activity['timestamp'],
                ])
                ->all(),
        ];
    }

    private function metric(array $metric): array
    {
        return [
            'allocated' => $metric['allocated'],
            'total' => $metric['total'],
            'percent' => $metric['percent'],
        ];
    }

    private function arrayFrom(mixed $value): array
    {
        if ($value instanceof Collection) {
            return $value->all();
        }

        return (array) $value;
    }
}
