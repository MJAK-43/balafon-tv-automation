<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vmix_connections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('host');
            $table->unsignedInteger('port')->default(8088);
            $table->string('api_password')->nullable();
            $table->unsignedInteger('timeout_ms')->default(3000);
            $table->string('health_status')->default('unknown');
            $table->timestamp('last_health_check_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vmix_connections');
    }
};
