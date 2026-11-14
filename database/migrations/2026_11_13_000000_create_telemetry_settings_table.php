<?php

use Illuminate\Database\Migrations\Migration;

// v4.7.0's migration, kept so a database imported from it has a file for every
// migration it ran. 5.x keeps telemetry in the settings table instead, and
// database/settings/2026_10_23_000000_create_telemetry_settings moves an
// imported panel's switch and id there, so on 5.x this creates nothing.
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
