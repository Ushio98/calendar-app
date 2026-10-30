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
            // none: 作業量なし / fixed: 毎日同じ量（workload_value） / daily: 日ごとに設定（schedule_workloads）
            $table->string('workload_mode', 10)->default('none')->after('workload_unit');
        });

        // 既存の予定で作業量が入っているものは「毎日同じ」として引き継ぐ
        DB::table('schedules')->whereNotNull('workload_value')->update(['workload_mode' => 'fixed']);

        Schema::create('schedule_workloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('value');
            $table->timestamps();

            $table->unique(['schedule_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_workloads');

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('workload_mode');
        });
    }
};
