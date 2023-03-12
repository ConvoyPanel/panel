<?php

namespace Convoy\Enums\Server;

enum MetricTimeframe: string
{
    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
