<?php

namespace App\Http\Controllers\Client;

use App\Actions\Auth\GeneratePasskeyRegisterOptionsAction;
use App\Actions\Auth\StorePasskeyAction;
use App\Data\User\PasskeyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Passkeys\RenamePasskeyRequest;
use App\Models\Passkey;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;
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

    public function store(Request $request, GenerateNewRecoveryCodes $generateRecoveryCodes)
    {
        $recoveryCodesCreated = empty($request->user()->two_factor_recovery_codes);
        $passkey = $this->storeAction->execute(
            authenticatable: $request->user(),
            passkeyJson: $request->getContent(),
            passkeyOptionsJson: $request->session()->get('passkeys.registration-options'),
            hostName: $request->getHost(),
            additionalProperties: ['name' => 'Passkey '.now()->format('Y-m-d')],
        );

        if ($recoveryCodesCreated) {
            $generateRecoveryCodes($request->user());
        }

        return response()->json([
            'data' => PasskeyData::from($passkey),
            'recovery_codes' => $recoveryCodesCreated
                ? json_decode(Fortify::currentEncrypter()->decrypt(
                    $request->user()->fresh()->two_factor_recovery_codes,
                ), true)
                : null,
        ]);
    }

    public function rename(RenamePasskeyRequest $request, Passkey $passkey)
    {
        $passkey->update($request->validated());

        return PasskeyData::from($passkey);
    }

    public function destroy(Passkey $passkey)
    {
        $user = $passkey->user;
        $passkey->delete();

        if (! $user->passkeys()->exists() && empty($user->two_factor_secret)) {
            $user->forceFill(['two_factor_recovery_codes' => null])->save();
        }

        return response()->noContent();
    }
}
