<?php

namespace Convoy\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ApplicationApiController extends Controller
{
    protected function returnNoContent(): JsonResponse
    {
        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
