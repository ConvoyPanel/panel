<?php

namespace App\Http\Controllers\Anchor;

use App\Http\Requests\Anchor\HeartbeatRequest;
use App\Models\Anchor;
use Illuminate\Validation\ValidationException;

class HeartbeatController
{
    public function __invoke(HeartbeatRequest $request)
    {
        /** @var Anchor $anchor */
        $anchor = $request->attributes->get('anchor');
        if ($request->string('mode')->toString() !== $anchor->mode->value) {
            throw ValidationException::withMessages([
                'mode' => 'The reported mode does not match this Anchor installation.',
            ]);
        }

        $anchor->update([
            'last_seen_at' => now(),
            'version' => $request->string('version')->toString(),
            'protocol_min' => $request->integer('protocol.min'),
            'protocol_max' => $request->integer('protocol.max'),
            'capabilities' => $request->input('capabilities'),
        ]);

        return response()->noContent();
    }
}
