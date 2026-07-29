<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('DRAFT');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['channel_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
