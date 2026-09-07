<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What a bulk action actually did.
 *
 * A selection is made by hand out of a table, so it will routinely contain rows the action does
 * not apply to — a system reservation among the addresses being released, an assigned address
 * among those being deleted. Failing the whole batch over one of those would make the feature
 * unusable, so they are skipped and counted, and the toast says so.
 */
#[MapInputName(SnakeCaseMapper::class)]
class BulkAddressResultData extends Data
{
    public function __construct(
        public string $action,
        public int $affected,
        public int $skipped,
    ) {}
}
