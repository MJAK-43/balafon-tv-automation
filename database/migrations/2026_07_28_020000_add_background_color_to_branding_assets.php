<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->string('text_background_color', 9)
                ->default('#07101D')
                ->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->dropColumn('text_background_color');
        });
    }
};
