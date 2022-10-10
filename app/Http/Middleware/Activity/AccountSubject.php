<?php

namespace Convoy\Http\Middleware\Activity;

use Closure;
use Convoy\Facades\LogTarget;
use Illuminate\Http\Request;

class AccountSubject
{
    /**
     * Sets the actor and default subject for all requests passing through this
     * middleware to be the currently logged in user.
     */
    public function handle(Request $request, Closure $next)
    {
        LogTarget::setActor($request->user());
        LogTarget::setSubject($request->user());

        return $next($request);
    }
}
