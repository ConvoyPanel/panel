<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            'UPDATE ip_addresses SET address = LOWER(address), gateway = LOWER(gateway), mac_address = LOWER(mac_address)',
        );
    }
};
