<?php

namespace App\Http\Controllers\Anchor;

use App\Http\Requests\Anchor\HeartbeatRequest;
use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;
use Illuminate\Validation\ValidationException;

class HeartbeatController
{
    public function __invoke(HeartbeatRequest $request)
    {
        /** @var Node|Relay|AnchorEnrollment $installation */
        $installation = $request->attributes->get('anchor');

        // A mismatch means this credential is being presented by something
        // other than the installation it was issued to.
        if ($request->string('mode')->toString() !== $installation->anchorMode()->value) {
            throw ValidationException::withMessages([
                'mode' => 'The reported mode does not match this Anchor installation.',
            ]);
        }

        $installation->recordAnchorHeartbeat([
            'version' => $request->string('version')->toString(),
            'protocol_min' => $request->integer('protocol.min'),
            'protocol_max' => $request->integer('protocol.max'),
            'capabilities' => $request->input('capabilities'),
        ]);

        return response()->noContent();
    }
}
