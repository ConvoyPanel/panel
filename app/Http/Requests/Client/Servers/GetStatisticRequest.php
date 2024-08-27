<?php

namespace Convoy\Http\Requests\Client\Servers;

use Convoy\Enums\Server\StatisticConsolidatorFunction;
use Convoy\Enums\Server\StatisticTimeRange;
use Convoy\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class GetStatisticRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'from' => ['required', Rule::enum(StatisticTimeRange::class)],
            'consolidator' => ['nullable', Rule::enum(StatisticConsolidatorFunction::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
