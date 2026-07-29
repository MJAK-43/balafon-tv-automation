<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branding_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('asset_type');
            $table->text('file_path')->nullable();
            $table->text('text_content')->nullable();
            $table->boolean('loop_enabled')->default(true);
            $table->string('status')->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_type', 'status']);
        });

        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->foreignId('logo_id')
                ->nullable()
                ->after('media_asset_id')
                ->constrained('branding_assets')
                ->nullOnDelete();
            $table->foreignId('announcement_id')
                ->nullable()
                ->after('logo_id')
                ->constrained('branding_assets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('announcement_id');
            $table->dropConstrainedForeignId('logo_id');
        });

        Schema::dropIfExists('branding_assets');
    }
};
