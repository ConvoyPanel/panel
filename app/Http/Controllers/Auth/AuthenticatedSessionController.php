<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthorizeTokenRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SSOToken;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function authorizeToken(Request $request)
    {
        $SSOToken = SSOToken::where('token', $request->token)->first();

        if (!isset($SSOToken))
        {
            return throw new NotFoundHttpException('Token doesn\'t exist');
        }

        // expire tokens if they're past a specific time
        $diff = Carbon::parse($SSOToken->created_at)->diffInMinutes(Carbon::now());

        if ($diff > 2 || $SSOToken->used)
        {
            return throw new UnauthorizedHttpException('', 'Token expired');
        }

        Auth::loginUsingId($SSOToken->user_id);

        $request->session()->regenerate();

        $SSOToken->update(['used' => true]);

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
