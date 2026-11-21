<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisioners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->text('script');
            // [{key, label, required}], in the order the forms show them.
            $table->jsonb('fields');
            $table->timestamps();
        });

        Schema::create('image_definition_provisioner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provisioner_id')->constrained()->cascadeOnDelete();
            // Scripts run in this order, lowest first.
            $table->unsignedSmallInteger('position');

            $table->unique(['image_definition_id', 'provisioner_id']);
        });

        Schema::create('provisioning_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Null once the server is gone. The row stays so the sweeper can
            // still delete the snippet the server left on its node.
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deployment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            // The PVE storage id and file name the snippet was written to.
            $table->string('storage');
            $table->string('snippet');
            $table->string('token_hash', 64)->unique();
            $table->string('status');
            // [{uuid, name}] as they were when the run started, so the result
            // still reads the same after a provisioner is renamed or deleted.
            $table->jsonb('provisioners');
            // [{provisioner, exit_code}] as the guest reported them.
            $table->jsonb('results')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('cleaned_up_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_runs');
        Schema::dropIfExists('image_definition_provisioner');
        Schema::dropIfExists('provisioners');
    }
};
