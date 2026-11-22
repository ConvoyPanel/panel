<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Services\Telemetry\TelemetryService;
use Illuminate\Validation\Validator;

class UpdateTelemetrySettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'enabled' => 'required|boolean',
        ];
    }

    /** @return list<callable> */
    public function after(TelemetryService $telemetry): array
    {
        return [
            function (Validator $validator) use ($telemetry) {
                if ($this->boolean('enabled') && ! $telemetry->allowedByEnvironment()) {
                    $validator->errors()->add('enabled', 'Telemetry is turned off in the environment.');
                }
            },
        ];
    }
}
