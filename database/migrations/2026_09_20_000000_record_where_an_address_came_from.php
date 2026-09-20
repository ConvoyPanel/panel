<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two columns that tell a claim about the world apart from a record of it.
 *
 * Every address row today is a claim: the panel generated it, or the allocator
 * handed it out, and either way Convoy decided it existed. Adopting a guest
 * introduces the other kind, an address that exists because a guest was seen
 * using it. A later re-scan has to be able to tell them apart, which is the
 * distinction every import tool in this space keeps: netbox-scanner tags the
 * rows it created so a scan never overwrites a hand-entered one, and phpIPAM
 * writes the literal `--autodiscovered--` into the description.
 *
 * `observed_at` dates the sighting. Null means never seen in a guest's config,
 * which is the correct and permanent answer for the whole pre-existing pool.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('origin')->default('generated');
            $table->timestamp('observed_at')->nullable();
        });

        Schema::table('servers', function (Blueprint $table) {
            // Whether Convoy owns this guest's cloud-init IP configuration. An
            // adopted guest whose ipconfig0 is `dhcp`, `ip6=auto` or simply
            // absent has an address the panel did not hand out and must not
            // overwrite; ServerNetworkService reads this and skips the write.
            $table->boolean('ipconfig_managed')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['origin', 'observed_at']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('ipconfig_managed');
        });
    }
};
