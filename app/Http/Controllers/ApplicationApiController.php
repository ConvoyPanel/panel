<?php

namespace Convoy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    // This can only be used if you are returning a success message after completing an action
    protected function returnInertiaResponse(Request $request, string $message, array $content = [])
    {
        if (count($content) > 0) {
            return $request->wantsJson()
                ? $this->returnContent($content)
                : back()->with('status', $message);
        } else {
            return $request->wantsJson()
                ? $this->returnNoContent()
                : back()->with('status', $message);
        }
    }

    protected function removeExtraDataProperty(array $attributes)
    {
        return $attributes ? $attributes['data'] : [];
    }
}
