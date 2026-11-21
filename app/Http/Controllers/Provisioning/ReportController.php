<?php

namespace App\Http\Controllers\Provisioning;

use App\Models\ProvisioningRun;
use App\Services\Provisioners\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Takes a guest's report of how its provisioner scripts exited.
 *
 * Answers 409 once the run can no longer take a report, so the runner stops
 * retrying a server that has been rebuilt since.
 */
class ReportController
{
    public function __invoke(Request $request, ProvisioningService $service): Response
    {
        $token = $request->bearerToken();

        $run = filled($token)
            ? ProvisioningRun::where('token_hash', ProvisioningRun::hashToken($token))->first()
            : null;

        if ($run === null) {
            throw new UnauthorizedHttpException('Bearer', 'Unknown provisioning token.');
        }

        if (! $run->status->acceptsReport()) {
            throw new ConflictHttpException('This server no longer takes a provisioning report.');
        }

        $validated = $request->validate([
            'results' => ['present', 'array', 'max:'.count($run->provisioners)],
            'results.*.provisioner' => [
                'required',
                'string',
                'distinct',
                Rule::in(collect($run->provisioners)->pluck('uuid')->all()),
            ],
            'results.*.exit_code' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $service->report($run, $validated['results']);

        return response()->noContent();
    }
}
