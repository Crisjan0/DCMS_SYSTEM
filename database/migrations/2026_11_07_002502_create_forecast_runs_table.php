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
        Schema::create('forecast_runs', function (Blueprint $table) {
            $table->id();

            $table->string('metric')->index();          // total_visits, new_patients, ...
            $table->string('algorithm');                // holt_winters | moving_average
            $table->unsignedInteger('horizon_days');    // how many days ahead
            $table->unsignedInteger('history_days');    // how many days of history were used
            $table->float('mape')->nullable();          // backtest accuracy (lower = better)
            $table->timestamp('ran_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_runs');
    }
};
