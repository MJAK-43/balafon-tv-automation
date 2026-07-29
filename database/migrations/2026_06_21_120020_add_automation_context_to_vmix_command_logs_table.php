<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vmix_command_logs', function (Blueprint $table): void {
            $table->foreignId('broadcast_run_id')->nullable()->after('vmix_connection_id')->constrained('broadcast_runs')->nullOnDelete();
            $table->foreignId('broadcast_run_item_id')->nullable()->after('broadcast_run_id')->constrained('broadcast_run_items')->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->after('broadcast_run_item_id')->constrained('schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vmix_command_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('schedule_id');
            $table->dropConstrainedForeignId('broadcast_run_item_id');
            $table->dropConstrainedForeignId('broadcast_run_id');
        });
    }
};
