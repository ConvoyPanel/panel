<?php

namespace Convoy\Services\Nodes;

use Convoy\Models\Node;
use Convoy\Models\Template;
use Convoy\Services\ProxmoxService;
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
