<?php

namespace App\Data\Admin\Settings;

use Spatie\LaravelData\Data;

/**
 * The panel-wide Anchor defaults, as edited on the admin Settings screen.
 * Unlike the bandwidth tier this one may be empty: falling through to APP_URL
 * is the right answer whenever the panel's own address is reachable.
 */
class AnchorSettingsData extends Data
{
    public function __construct(
        public ?string $panelUrl,
    ) {}
}
