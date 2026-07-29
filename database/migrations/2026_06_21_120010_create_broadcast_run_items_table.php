<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_run_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('broadcast_run_id')->constrained('broadcast_runs')->cascadeOnDelete();
            $table->foreignId('playlist_item_id')->constrained('playlist_items')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('state')->default('PENDING');
            $table->string('vmix_input_key')->nullable();
            $table->string('vmix_input_number')->nullable();
            $table->unsignedInteger('last_known_position_ms')->nullable();
            $table->unsignedInteger('last_known_duration_ms')->nullable();
            $table->json('context')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique(['broadcast_run_id', 'sequence']);
            $table->index(['state', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_run_items');
    }
};
