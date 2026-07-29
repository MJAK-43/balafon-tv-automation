<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->unsignedSmallInteger('ticker_speed')
                ->default(120)
                ->after('text_font');
        });
    }

    public function down(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->dropColumn('ticker_speed');
        });
    }
};
