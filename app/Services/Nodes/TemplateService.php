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
    public function listTemplates(bool $showVisibleOnly = false)
    {
        if ($showVisibleOnly) {
            return Template::where('visible', true)->whereHas('server', function ($q) {
                $q->where('node_id', $this->node->id);
            })->with(['server' => function ($query) {
                $query->select(['id', 'vmid', 'name']);
            }])->get(['id', 'server_id'])->toArray();
        } else {
            return Template::whereHas('server', function ($q) {
                $q->where('node_id', $this->node->id);
            })->with(['server' => function ($query) {
                $query->select(['id', 'vmid', 'name']);
            }])->get(['id', 'server_id'])->toArray();
        }
    }
}
