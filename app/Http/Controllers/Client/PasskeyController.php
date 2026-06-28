<?php

namespace App\Http\Controllers\Client;

use App\Actions\Auth\GeneratePasskeyRegisterOptionsAction;
use App\Actions\Auth\StorePasskeyAction;
use App\Data\User\PasskeyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Passkeys\RenamePasskeyRequest;
use App\Models\Passkey;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class PasskeyController extends Controller
{
    public function __construct(
        private GeneratePasskeyRegisterOptionsAction $generateOptionsAction,
        private StorePasskeyAction $storeAction,
    ) {}

    public function index(Request $request)
    {
        return PasskeyData::collect($request->user()->passkeys, DataCollection::class);
    }

    public function create(Request $request)
    {
        $options = $this->generateOptionsAction->execute($request->user());

        $request->session()->put('passkeys.registration-options', $options);

        return $options;
    }

    public function store(Request $request)
    {
        $passkey = $this->storeAction->execute(
            user: $request->user(),
            name: 'Passkey '.now()->format('Y-m-d'),
            passkeyJson: $request->getContent(),
            passkeyOptionsJson: $request->session()->get('passkeys.registration-options'),
            hostName: $request->getHost(),
        );

        return PasskeyData::from($passkey);
    }

    public function rename(RenamePasskeyRequest $request, Passkey $passkey)
    {
        $passkey->update($request->validated());

        return PasskeyData::from($passkey);
    }

    public function destroy(Passkey $passkey)
    {
        $passkey->delete();

        return response()->noContent();
    }
}
