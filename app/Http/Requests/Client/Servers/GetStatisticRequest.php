<?php

namespace App\Http\Requests\Client\Servers;

use App\Enums\Server\StatisticConsolidatorFunction;
use App\Enums\Server\StatisticTimeRange;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
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
        return $this->user()->can('viewStatistics', $this->parameter('server', Server::class));
    }
}
