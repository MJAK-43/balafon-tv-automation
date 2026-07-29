<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('playlist_items')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasColumn('playlist_items', 'item_type')) {
            DB::statement('ALTER TABLE playlist_items DROP COLUMN IF EXISTS item_type');
        }

        if (Schema::hasColumn('playlist_items', 'duration')) {
            DB::statement('ALTER TABLE playlist_items DROP COLUMN IF EXISTS duration');
        }

        if (Schema::hasColumn('playlist_items', 'media_asset_id')) {
            DB::statement('ALTER TABLE playlist_items ALTER COLUMN media_asset_id SET NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('playlist_items', 'item_type')) {
            Schema::table('playlist_items', function (Blueprint $table): void {
                $table->string('item_type')->default('media');
            });
        }

        if (! Schema::hasColumn('playlist_items', 'duration')) {
            Schema::table('playlist_items', function (Blueprint $table): void {
                $table->unsignedInteger('duration')->default(0);
            });
        }

        if (Schema::hasColumn('playlist_items', 'media_asset_id')) {
            DB::statement('ALTER TABLE playlist_items ALTER COLUMN media_asset_id DROP NOT NULL');
        }
    }
};
