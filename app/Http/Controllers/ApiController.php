<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    protected function returnNoContent(): JsonResponse
    {
        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
