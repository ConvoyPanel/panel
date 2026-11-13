<?php

namespace App\Data\Admin\Settings;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PermissionSettingsData extends Data
{
    public function __construct(
        public bool $allowGuestAccounts,
        /** How many guest accounts exist, so the screen can say what turning the switch off locks out. */
        public int $guestAccountCount,
    ) {}
}
