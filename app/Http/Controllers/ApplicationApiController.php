<?php

namespace Convoy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApplicationApiController extends Controller
{
    protected function returnNoContent(): JsonResponse
    {
        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    protected function returnContent($payload, $code = Response::HTTP_OK): Response
    {
        return new Response($payload, $code);
    }
}
