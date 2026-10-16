<?php

use App\Enums\User\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Every account that exists today was provisioned by the operator, so `standard` is
            // both the default and the correct backfill.
            $table->string('type', 16)->default(UserType::STANDARD->value)->after('email');

            // Filtered and counted on every admin user listing.
            $table->index('type');
        });

        // A guest exists only to hold shares, so it can never be an admin. Enforced in the
        // database rather than only in the request layer: this is the one invariant that turns a
        // shared server into a compromised panel if it is ever missed.
        DB::statement(<<<'SQL'
            ALTER TABLE users
            ADD CONSTRAINT users_guest_holds_no_admin_role
            CHECK (type <> 'guest' OR admin_role_id IS NULL)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_guest_holds_no_admin_role');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
