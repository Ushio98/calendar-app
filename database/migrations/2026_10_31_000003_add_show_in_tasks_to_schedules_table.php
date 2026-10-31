<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('show_in_tasks')->default(false)->after('workload_mode');
        });

        // これまでは作業量のある予定が「本日の作業」に出ていたので、見え方が変わらないように引き継ぐ
        DB::table('schedules')->where('workload_mode', '!=', 'none')->update(['show_in_tasks' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('show_in_tasks');
        });
    }
};
