<?php

namespace Convoy\Enums\Server;

enum StatisticTimeRange: string
{
    case HOUR_AGO = 'hour';
    case DAY_AGO = 'day';
    case WEEK_AGO = 'week';
    case MONTH_AGO = 'month';
    case YEAR_AGO = 'year';
}
