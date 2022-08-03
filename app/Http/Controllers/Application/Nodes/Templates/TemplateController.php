<?php

namespace App\Http\Controllers\Application\Nodes\Templates;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Template;
use App\Services\Nodes\TemplateService;
use App\Transformers\Application\TemplateTransformer;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(private TemplateService $templateService)
    {

    }

    public function index(Node $node)
    {
        $data = $this->templateService->setNode($node)->listTemplates();

        return fractal()->collection($data)->transformWith(new TemplateTransformer())->respond();
    }

    public function show(Node $node, Template $template)
    {
        return fractal($template, new TemplateTransformer())->respond();
    }
}
