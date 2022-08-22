<?php

namespace App\Http\Controllers\Admin\Templates;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Template;
use App\Services\Nodes\TemplateService;
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
