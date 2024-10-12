<?php

namespace App\Extensions\Lcobucci\JWT\Validation;

use DateTimeImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
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
