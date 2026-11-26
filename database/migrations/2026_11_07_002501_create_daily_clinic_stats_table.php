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
        // One row per clinic day = the "time series" fed to the forecasting engine
        Schema::create('daily_clinic_stats', function (Blueprint $table) {
            $table->id();

            $table->date('date')->unique();
            $table->unsignedInteger('total_visits')->default(0);
            $table->unsignedInteger('new_patients')->default(0);
            $table->unsignedInteger('no_shows')->default(0);
            $table->unsignedInteger('cancellations')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_clinic_stats');
    }
};
