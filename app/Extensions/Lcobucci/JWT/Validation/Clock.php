<?php

namespace Convoy\Extensions\Lcobucci\JWT\Validation;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    private CarbonInterface $date;

    public function __construct(?CarbonInterface $date = null)
    {
        $this->date = $date ?? CarbonImmutable::now();
    }

    public function now(): DateTimeImmutable
    {
        return $this->date->toDateTimeImmutable();
    }
}
