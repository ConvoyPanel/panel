<?php

namespace App\Data\Node\Testing;

use App\Data\Node\Status\NodeStatusData;
use App\Enums\Node\Testing\ConnectionErrorCode;
use Spatie\LaravelData\Data;

class ConnectionResultData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $errorMessage = null,
        public ?ConnectionErrorCode $errorCode = null,
        public ?NodeStatusData $data = null,
    ) {}
}
