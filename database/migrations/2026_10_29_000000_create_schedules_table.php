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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('color', 20)->nullable();
            $table->text('memo')->nullable();
            $table->unsignedInteger('workload_value')->nullable();
            $table->string('workload_unit', 50)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
