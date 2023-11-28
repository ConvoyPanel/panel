<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Requests\Admin\Coterms\StoreCotermRequest;
use Convoy\Http\Requests\Admin\Coterms\UpdateAttachedNodesRequest;
use Convoy\Http\Requests\Admin\Coterms\UpdateCotermRequest;
use Convoy\Models\Coterm;
use Illuminate\Http\Request;

class CotermController
{
    public function index()
    {
        
    }

    public function store(StoreCotermRequest $request)
    {

    }

    public function update(UpdateCotermRequest $request, Coterm $coterm)
    {
    }

    public function updateAttachedNodes(UpdateAttachedNodesRequest $request, Coterm $coterm)
    {

    }

    public function destroy(Coterm $coterm)
    {
    }
}
