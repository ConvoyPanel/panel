<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn([
                'should_create_vm',
                'delete_successful',
                'deleted_vm_at',
                'build_successful',
                'build_progress',
                'built_vm_at',
                'sync_successful',
                'synced_vm_at',
                'created_at'
            ]);

            $table->after('template_id', function (Blueprint $table) {
                $table->string('type');
                $table->string('status');
            });

            $table->after('start_on_completion', function (Blueprint $table) {
                $table->timestamp('requested_at');
                $table->timestamp('completed_at')->nullable();
            });
        });

        Schema::create('deployment_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status');
            $table->bigInteger('progress_total')->nullable();
            $table->bigInteger('progress_current')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn(['type', 'status', 'requested_at', 'completed_at']);

            $table->boolean('should_create_vm')->after('template_id');

            $table->after('start_on_completion', function (Blueprint $table) {
                $table->boolean('delete_successful');
                $table->timestamp('deleted_vm_at')->nullable();
                $table->boolean('build_successful');
                $table->integer('build_progress');
                $table->timestamp('built_vm_at')->nullable();
                $table->boolean('sync_successful');
                $table->timestamp('synced_vm_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        });

        Schema::dropIfExists('deployment_steps');
    }
};
