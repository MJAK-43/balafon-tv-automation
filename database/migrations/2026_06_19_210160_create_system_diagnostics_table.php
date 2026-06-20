<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_diagnostics', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('machine_name')->nullable();
            $table->string('os_name');
            $table->string('os_version')->nullable();
            $table->boolean('vmix_detected')->default(false);
            $table->string('vmix_version')->nullable();
            $table->boolean('vmix_api_reachable')->default(false);
            $table->json('tested_endpoints')->nullable();
            $table->string('local_ip')->nullable();
            $table->unsignedBigInteger('total_disk_bytes')->nullable();
            $table->unsignedBigInteger('free_disk_bytes')->nullable();
            $table->unsignedBigInteger('total_memory_bytes')->nullable();
            $table->unsignedBigInteger('available_memory_bytes')->nullable();
            $table->unsignedInteger('cpu_cores')->nullable();
            $table->decimal('cpu_load_percent', 8, 2)->nullable();
            $table->boolean('postgres_ok')->default(false);
            $table->boolean('redis_ok')->default(false);
            $table->boolean('scheduler_ok')->default(false);
            $table->boolean('queue_workers_ok')->default(false);
            $table->json('context')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_diagnostics');
    }
};
