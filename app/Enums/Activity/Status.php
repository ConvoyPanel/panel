<?php

namespace Convoy\Enums\Activity;

enum Status: string
{
    case OK = 'ok';
    case ERROR = 'error';
    case RUNNING = 'running';
}