<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('vmix_connection_id')->constrained('vmix_connections')->cascadeOnDelete();
            $table->string('state')->default('PENDING');
            $table->foreignId('current_playlist_item_id')->nullable()->constrained('playlist_items')->nullOnDelete();
            $table->foreignId('current_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('current_sequence')->nullable();
            $table->json('context')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_runs');
    }
};
