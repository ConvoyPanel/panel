<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the operator's guess at whether a storage is shared.
 *
 * `pve_shared` is discovered from Proxmox and is authoritative; `is_shareable`
 * was typed in at registration and could disagree with it, which is exactly the
 * drift the discovery work exists to remove.
 *
 * It was also unreachable. The create and edit forms hardcoded `false` and never
 * rendered a control for it, so no storage created through the panel could ever
 * have it set -- which means the `display_name` rule it gated never fired either.
 * Nothing is being taken away that anyone could have used.
 *
 * `down()` restores the column at its original default rather than trying to
 * reconstruct intent from `pve_shared`: the two never meant the same thing, and
 * inventing values would be worse than the honest `false` every row already had.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn('is_shareable');
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->boolean('is_shareable')->default(false);
        });
    }
};
