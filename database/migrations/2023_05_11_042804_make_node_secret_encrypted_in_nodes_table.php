<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $nodes = DB::table('nodes')->get()->toArray();

            foreach ($nodes as $node) {
                DB::table('nodes')
                    ->where('id', $node->id)
                    ->update(['secret' => app(Encrypter::class)->encrypt($node->secret)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $nodes = DB::table('nodes')->get()->toArray();

            foreach ($nodes as $node) {
                DB::table('nodes')
                    ->where('id', $node->id)
                    ->update(['secret' => app(Encrypter::class)->decrypt($node->secret)]);
            }
        });
    }
};
