<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('playlist_items', 'uuid')) {
            Schema::table('playlist_items', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->after('id');
            });

            DB::table('playlist_items')->orderBy('id')->get(['id'])->each(function (object $row): void {
                DB::table('playlist_items')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

            DB::statement('ALTER TABLE playlist_items ALTER COLUMN uuid SET NOT NULL');

            Schema::table('playlist_items', function (Blueprint $table): void {
                $table->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('playlist_items', 'uuid')) {
            Schema::table('playlist_items', function (Blueprint $table): void {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
