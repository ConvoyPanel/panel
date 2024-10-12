<?php

namespace App\Enums\Api;

enum ApiKeyType: string
{
    case ACCOUNT = 'account';
    case APPLICATION = 'application';
}
