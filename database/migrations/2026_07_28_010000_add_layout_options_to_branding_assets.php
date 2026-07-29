<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->string('logo_position')->default('TOP_RIGHT')->after('text_content');
            $table->unsignedTinyInteger('logo_scale')->default(20)->after('logo_position');
            $table->string('text_color', 9)->default('#FFFFFF')->after('logo_scale');
            $table->string('text_font')->default('SEGOE_UI')->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('branding_assets', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_position',
                'logo_scale',
                'text_color',
                'text_font',
            ]);
        });
    }
};
