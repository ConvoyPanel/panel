<?php

namespace Convoy\Http\Controllers\Application\Nodes\Templates;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Node;
use Convoy\Models\Template;
use Convoy\Services\Nodes\TemplateService;
use Convoy\Transformers\Application\TemplateTransformer;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(private TemplateService $templateService)
    {

    }

    public function index(Node $node)
    {
        $data = $this->templateService->setNode($node)->getTemplates();

        return fractal()->collection($data)->transformWith(new TemplateTransformer())->respond();
    }

    public function show(Node $node, Template $template)
    {
        $template->load('server');

        return fractal()->item($template->toArray())->transformWith(new TemplateTransformer())->respond();
    }
}
