<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            // delivery: 納品日 / release: リリース日 / sale: 販売日 / event: イベント日
            $table->string('milestone_type', 20)->nullable();
            $table->date('milestone_date')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            // プロジェクトを削除しても予定は残し、プロジェクトの設定だけを外す
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });

        Schema::dropIfExists('projects');
    }
};
