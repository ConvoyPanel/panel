<?php

namespace App\Http\Controllers\Anchor;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\Anchor\ConsumeEnrollmentRequest;
use App\Models\Anchor;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EnrollmentController
{
    public function __invoke(ConsumeEnrollmentRequest $request)
    {
        $anchor = DB::transaction(function () use ($request) {
            $anchor = Anchor::where(
                'enrollment_token_hash',
                hash('sha256', $request->string('token')->toString()),
            )->lockForUpdate()->first();

            if ($anchor === null || $anchor->enrollment_expires_at?->isPast()) {
                throw new UnprocessableEntityHttpException('The enrollment token is invalid or expired.');
            }

            $anchor->update([
                'enrollment_token_hash' => null,
                'enrollment_expires_at' => null,
                'enrolled_at' => now(),
            ]);

            return $anchor;
        });

        return response()->json([
            'config' => [
                'mode' => $anchor->mode->value,
                'listen_addr' => $anchor->mode === AnchorMode::AGENT ? '127.0.0.1:2115' : '0.0.0.0:2115',
                'installation_id' => $anchor->uuid,
                'secret' => $anchor->secret,
                'panel_url' => rtrim(config('app.url'), '/').'/',
                'public_url' => $anchor->public_url,
                'agent' => ['qm_path' => '/usr/sbin/qm'],
            ],
        ]);
    }
}
