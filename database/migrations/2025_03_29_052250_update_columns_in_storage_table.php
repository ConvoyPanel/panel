<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->renameColumn('nickname', 'display_name');
            $table->renameColumn('has_kvm', 'stores_kvm');
            $table->renameColumn('has_lxc', 'stores_lxc');
            $table->renameColumn('has_lxc_templates', 'stores_lxc_templates');
            $table->renameColumn('has_backups', 'stores_backups');
            $table->renameColumn('has_iso', 'stores_iso');
            $table->renameColumn('has_snippets', 'stores_snippets');
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->renameColumn('display_name', 'nickname');
            $table->renameColumn('stores_kvm', 'has_kvm');
            $table->renameColumn('stores_lxc', 'has_lxc');
            $table->renameColumn('stores_lxc_templates', 'has_lxc_templates');
            $table->renameColumn('stores_backups', 'has_backups');
            $table->renameColumn('stores_iso', 'has_iso');
            $table->renameColumn('stores_snippets', 'has_snippets');
        });
    }
};
