<?php

namespace App\Http\Controllers\Client\Account;

use App\Data\User\SSHKeyData;
use App\Http\Requests\Client\Account\StoreSSHKeyRequest;
use App\Models\SSHKey;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SSHKeyController
{
    public function index(Request $request)
    {
        return SSHKeyData::collect(
            $request->user()->sshKeys()->latest('id')->get(),
            DataCollection::class,
        );
    }

    public function store(StoreSSHKeyRequest $request)
    {
        $key = $request->user()->sshKeys()->create($request->validated());

        return SSHKeyData::fromModel($key);
    }

    public function destroy(Request $request, SSHKey $sshKey)
    {
        // 404 (not 403) on someone else's key, so a key id can't be probed.
        if ($sshKey->user_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }

        $sshKey->delete();

        return response()->noContent();
    }
}
