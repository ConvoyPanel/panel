<?php

namespace App\Services\Nodes;

use App\Models\Node;
use App\Models\Template;
use App\Services\ProxmoxService;
use Webmozart\Assert\Assert;

class TemplateService extends ProxmoxService
{
    public function getTemplates(bool $showVisibleOnly = false)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $builder = Template::whereHas('server', function ($q) {
            $q->where('node_id', $this->node->id);
        })->with('server:id,vmid,name');

        if ($showVisibleOnly) {
            $builder = $builder->where('visible', true);
        }

        return $builder->get(['id', 'server_id'])->toArray();
    }
}
