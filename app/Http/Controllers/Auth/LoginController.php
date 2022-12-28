<?php

namespace Convoy\Http\Controllers\Auth;

use Carbon\Carbon;
use Convoy\Http\Controllers\Controller;
use Convoy\Models\SSOToken;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginController extends Controller
{
    public function __construct(private ViewFactory $view)
    {
    }

    public function index(): View
    {
        return $this->view->make('app');
    }

    public function authorizeToken(Request $request)
    {
        $SSOToken = SSOToken::where('token', $request->token)->first();

        if (! isset($SSOToken)) {
            return throw new NotFoundHttpException('Token doesn\'t exist');
        }

        // expire tokens if they're past a specific time
        $diff = Carbon::parse($SSOToken->created_at)->diffInMinutes(Carbon::now());

        if ($diff > 2 || $SSOToken->used) {
            return throw new UnauthorizedHttpException('', 'Token expired');
        }

        Auth::loginUsingId($SSOToken->user_id);

        $request->session()->regenerate();

        $SSOToken->update(['used' => true]);

        return redirect()->route('dashboard');
    }
}
