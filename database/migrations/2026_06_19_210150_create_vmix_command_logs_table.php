<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vmix_command_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vmix_connection_id')->constrained()->cascadeOnDelete();
            $table->string('command_name');
            $table->string('request_url');
            $table->json('request_payload')->nullable();
            $table->unsignedInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vmix_command_logs');
    }
};
