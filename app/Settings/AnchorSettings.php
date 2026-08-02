<?php

namespace App\Settings;

use App\Models\Anchor;
use Spatie\LaravelSettings\Settings;

/**
 * Panel-wide Anchor defaults. Currently the middle tier of the panel-URL
 * cascade that decides how an Anchor reaches this panel:
 *
 *   anchor.panel_url_override -> these defaults -> config('app.url')
 *
 * It exists because the common case is one panel address that every Anchor
 * needs and APP_URL cannot supply (a private tunnel, a split DNS horizon),
 * which per-Anchor overrides would force you to repeat on every record. The
 * per-Anchor field remains for fleets whose Anchors genuinely reach the panel
 * at different addresses.
 */
class AnchorSettings extends Settings
{
    /**
     * Where Anchors should reach the panel, or an empty string to use APP_URL.
     *
     * Stored as a string rather than a nullable one so the shape never changes;
     * {@see Anchor::panelUrl()} treats empty as "not set".
     */
    public string $panel_url = '';

    public static function group(): string
    {
        return 'anchor';
    }
}
