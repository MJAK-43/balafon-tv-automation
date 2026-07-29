<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('media_type');
            $table->text('file_path')->unique();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('READY');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['media_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
