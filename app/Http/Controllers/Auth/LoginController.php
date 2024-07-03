<?php

namespace Convoy\Http\Controllers\Auth;

use Convoy\Exceptions\Service\Api\InvalidJWTException;
use Convoy\Http\Controllers\Controller;
use Convoy\Models\SSOToken;
use Convoy\Models\User;
use Convoy\Services\Api\JWTService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginController extends Controller
{
    public function __construct(private ViewFactory $view, private JWTService $JWTService)
    {
    }

    public function index(): View
    {
        return $this->view->make('app');
    }

    public function authorizeToken(Request $request)
    {
        try {
            $token = $this->JWTService->decode(config('app.url'), $request->token);
        } catch (InvalidJWTException) {
            throw new UnauthorizedHttpException('', 'Invalid JWT token');
        }

        /** @var User $user */
        $user = User::where('uuid', '=', $token->claims()->get('user_uuid'))->first();

        if (!$user) {
            throw new UnauthorizedHttpException('', 'Invalid JWT claims');
        }

        Auth::loginUsingId($user->id);

        $request->session()->regenerate();

        return redirect()->route('index');
    }
}
