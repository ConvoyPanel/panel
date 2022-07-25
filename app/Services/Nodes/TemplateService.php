<?php

namespace App\Services\Nodes;

use App\Models\Server;
use App\Models\Template;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class TemplateService extends ProxmoxService
{
    public function listTemplates()
    {
        return Template::with(['server' => function ($query) {
            $query->where('node_id', $this->node->id)->select(['id', 'vmid', 'name']);
        }])->get(['id', 'server_id'])->toArray();
    }
}
