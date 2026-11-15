<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every size column held whole mebibytes, rounded down, and StorageSizeCast
 * turned them into bytes on the way out. They now hold bytes, in bigints.
 *
 * Runs last on every path into this schema -- a fresh install, the v4 cutover
 * and an upgrade from 5.x -- so whatever came before it left these columns in
 * mebibytes. Zero, null and the -1 "unlimited" sentinel stay as they are.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const COLUMNS = [
        'servers' => ['memory', 'disk', 'bandwidth_usage', 'bandwidth_limit', 'backup_size_limit'],
        'nodes' => ['memory'],
        'storages' => ['size', 'reserved_bytes'],
        'server_disks' => ['size'],
        'backups' => ['size'],
        'iso_library' => ['size'],
        'image_versions' => ['size'],
    ];

    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->bigInteger('memory')->change();
            $table->bigInteger('disk')->change();
            $table->bigInteger('bandwidth_usage')->default(0)->change();
            $table->bigInteger('bandwidth_limit')->change();
            $table->bigInteger('backup_size_limit')->change();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->bigInteger('memory')->change();
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->bigInteger('size')->change();
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->bigInteger('size')->change();
        });

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->where($column, '>', 0)->update([$column => DB::raw("{$column} * 1048576")]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->where($column, '>', 0)->update([$column => DB::raw("{$column} / 1048576")]);
            }
        }

        Schema::table('servers', function (Blueprint $table) {
            $table->integer('memory')->change();
            $table->integer('disk')->change();
            $table->integer('bandwidth_usage')->default(0)->change();
            $table->integer('bandwidth_limit')->change();
            $table->integer('backup_size_limit')->change();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('memory')->change();
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->integer('size')->change();
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->integer('size')->change();
        });
    }
};
