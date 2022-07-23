<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApplicationApiController extends Controller
{
    protected function returnNoContent(): Response
    {
        return (new Response('', Response::HTTP_NO_CONTENT))->header('X-Inertia', true);
    }

    protected function returnContent($payload, $code = Response::HTTP_OK): Response
    {
        return (new Response($payload, $code))->header('X-Inertia', true);
    }

    // This can only be used if you are returning a success message after completing an action
    protected function returnInertiaResponse(Request $request, String $message, array $content = [])
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
