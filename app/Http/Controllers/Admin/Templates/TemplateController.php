<?php

namespace Convoy\Http\Controllers\Admin\Templates;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Node;
use Convoy\Models\Template;
use Convoy\Services\Nodes\TemplateService;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(private TemplateService $templateService)
    {

    }

    public function index(Node $node)
    {
        return $this->templateService->setNode($node)->getTemplates();
    }

    public function show(Node $node, Template $template)
    {
        return $template;
    }
}
